import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import { execFile } from 'node:child_process';
import { assertCssContract } from './assert-css-contract.mjs';
import { assertWellFormedUnicode, parseStrictJson, readStrictJsonFile } from './strict-json.mjs';

const execFileAsync = promisify(execFile);
const fixtureDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(fixtureDirectory, '../../..');
const documentationPath = path.join(repositoryRoot, 'docs/frontend-compatibility.md');
const sourceBaselinePath = path.join(fixtureDirectory, 'source-baseline.json');
const sourceManifestPath = path.join(fixtureDirectory, 'source-files.json');
const sourceDigestCanonicalization = 'aura-source-records-v2-length-prefixed';
const npmCliPath = process.env.npm_execpath;
const tailwindCliPath = path.join(
    repositoryRoot,
    'node_modules',
    'tailwindcss',
    'lib',
    'cli.js',
);
const temporaryRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'aura-frontend-compatibility-'));
const sourceBaseline = await readStrictJsonFile(sourceBaselinePath, 'Frontend source baseline');
const sourceManifest = await readStrictJsonFile(sourceManifestPath, 'Frontend source manifest');

assert.ok(npmCliPath, 'Run this fixture through npm run test:frontend-compatibility.');
validateSourceBaseline(sourceBaseline);

async function copyFile(source, destination) {
    await fs.mkdir(path.dirname(destination), { recursive: true });
    await fs.copyFile(source, destination);
}

function isWithinRoot(rootPath, candidatePath) {
    const relativePath = path.relative(rootPath, candidatePath);

    return relativePath !== ''
        && relativePath !== '..'
        && ! relativePath.startsWith(`..${path.sep}`)
        && ! path.isAbsolute(relativePath);
}

function toRepositoryRelativePath(rootPath, sourcePath) {
    return path.relative(rootPath, sourcePath).split(path.sep).join('/');
}

async function resolveSelectedSources(sourceRoot, manifest) {
    const realSourceRoot = await fs.realpath(sourceRoot);
    const caseInsensitivePaths = new Set();
    const fileIdentities = new Set();
    const resolvedSources = [];

    for (const entry of manifest) {
        const requestedPath = path.resolve(realSourceRoot, ...entry.path.split('/'));
        assert.ok(isWithinRoot(realSourceRoot, requestedPath), `Source path escapes source root: ${entry.path}`);

        const realSourcePath = await fs.realpath(requestedPath);
        assert.ok(isWithinRoot(realSourceRoot, realSourcePath), `Resolved source path escapes source root: ${entry.path}`);

        const canonicalRelativePath = toRepositoryRelativePath(realSourceRoot, realSourcePath);
        assert.equal(
            entry.path,
            canonicalRelativePath,
            `Source path must equal its canonical repository-relative realpath: ${entry.path} -> ${canonicalRelativePath}`,
        );

        const caseInsensitiveIdentity = canonicalRelativePath.normalize('NFC').toLowerCase();
        assert.equal(
            caseInsensitivePaths.has(caseInsensitiveIdentity),
            false,
            `Source records must have unique case-insensitive path identity: ${entry.path}`,
        );
        caseInsensitivePaths.add(caseInsensitiveIdentity);

        const stats = await fs.stat(realSourcePath, { bigint: true });
        assert.ok(stats.isFile(), `Selected source must resolve to a regular file: ${entry.path}`);
        const fileIdentity = `${stats.dev}:${stats.ino}`;
        assert.equal(fileIdentities.has(fileIdentity), false, `Source records must have unique file identity: ${entry.path}`);
        fileIdentities.add(fileIdentity);

        resolvedSources.push({
            entry,
            sourcePath: realSourcePath,
        });
    }

    return resolvedSources;
}

function validateSourceBaseline(baseline) {
    assert.ok(baseline && typeof baseline === 'object' && ! Array.isArray(baseline), 'Source baseline must be an object');
    assert.deepEqual(
        Object.keys(baseline).sort(),
        ['algorithm', 'canonicalization', 'expectedDigest', 'manifest', 'sourceBytes'],
        'Source baseline must contain only supported contract settings',
    );

    for (const field of ['algorithm', 'canonicalization', 'expectedDigest', 'manifest', 'sourceBytes']) {
        assertWellFormedUnicode(baseline[field], `Source baseline ${field}`);
    }

    assert.equal(baseline.manifest, path.basename(sourceManifestPath), 'Source baseline must name the selected-source manifest');
    assert.equal(baseline.algorithm, 'sha256', 'Source baseline must use SHA-256');
    assert.equal(baseline.canonicalization, sourceDigestCanonicalization, 'Source baseline must use canonical v2 records');
    assert.equal(baseline.sourceBytes, 'exact', 'Source baseline must authenticate exact source bytes');
    assert.match(baseline.expectedDigest, /^[a-f0-9]{64}$/, 'Source baseline must contain a SHA-256 digest');
}

function validateSourceManifest(manifest) {
    assert.ok(Array.isArray(manifest) && manifest.length > 0, 'Source manifest must select Aura source files');

    const selectedPaths = new Set();

    for (const [index, entry] of manifest.entries()) {
        assert.ok(entry && typeof entry === 'object' && ! Array.isArray(entry), `Source manifest record ${index} must be an object`);
        assert.deepEqual(
            Object.keys(entry).sort(),
            ['classes', 'path'],
            `Source manifest record ${index} must contain exactly path and classes`,
        );
        assert.ok(typeof entry.path === 'string' && entry.path.length > 0, `Source manifest record ${index} needs a path`);
        assertWellFormedUnicode(entry.path, `Source manifest path ${index}`);
        assert.equal(entry.path.includes('\\'), false, `Source manifest path must use forward slashes: ${entry.path}`);
        assert.equal(path.posix.normalize(entry.path), entry.path, `Source manifest path must be canonical: ${entry.path}`);
        assert.equal(path.posix.isAbsolute(entry.path), false, `Source manifest path must be relative: ${entry.path}`);
        assert.equal(entry.path.split('/').includes('..'), false, `Source manifest path cannot traverse upward: ${entry.path}`);
        assert.equal(selectedPaths.has(entry.path), false, `Source manifest path must be unique: ${entry.path}`);
        assert.ok(Array.isArray(entry.classes) && entry.classes.length > 0, `${entry.path} must declare expected classes`);
        assert.ok(
            entry.classes.every((className) => typeof className === 'string' && className.length > 0),
            `${entry.path} classes must be non-empty strings`,
        );

        for (const [classIndex, className] of entry.classes.entries()) {
            assertWellFormedUnicode(className, `${entry.path} class ${classIndex}`);
        }

        assert.equal(new Set(entry.classes).size, entry.classes.length, `${entry.path} classes must be unique`);

        selectedPaths.add(entry.path);
    }
}

function unsigned64(value) {
    assert.ok(Number.isSafeInteger(value) && value >= 0, `Canonical length must be a non-negative safe integer: ${value}`);

    const encoded = Buffer.alloc(8);
    encoded.writeBigUInt64BE(BigInt(value));

    return encoded;
}

function updateLengthPrefixed(digest, value, label) {
    let bytes;

    if (Buffer.isBuffer(value)) {
        bytes = value;
    } else {
        assertWellFormedUnicode(value, label);
        bytes = Buffer.from(value, 'utf8');
    }

    digest.update(unsigned64(bytes.length));
    digest.update(bytes);
}

function calculateSourceDigest(records) {
    const digest = crypto.createHash('sha256');

    updateLengthPrefixed(digest, sourceDigestCanonicalization, 'Source digest canonicalization');
    digest.update(unsigned64(records.length));

    for (const [recordIndex, record] of records.entries()) {
        updateLengthPrefixed(digest, record.path, `Source digest record ${recordIndex} path`);
        digest.update(unsigned64(record.classes.length));

        for (const [classIndex, className] of record.classes.entries()) {
            updateLengthPrefixed(digest, className, `Source digest record ${recordIndex} class ${classIndex}`);
        }

        updateLengthPrefixed(digest, record.source, `Source digest record ${recordIndex} bytes`);
    }

    return digest.digest('hex');
}

function snapshotFromRecords(records) {
    return {
        count: records.length,
        digest: calculateSourceDigest(records),
        records,
    };
}

async function inspectAuraSources(sourceRoot, manifest = sourceManifest) {
    validateSourceManifest(manifest);

    const records = [];
    const resolvedSources = await resolveSelectedSources(sourceRoot, manifest);

    for (const { entry, sourcePath } of resolvedSources) {
        const source = await fs.readFile(sourcePath);
        const sourceText = source.toString('utf8');

        for (const className of entry.classes) {
            assert.ok(sourceText.includes(className), `${entry.path} no longer contains ${className}`);
        }

        records.push({
            path: entry.path,
            classes: [...entry.classes],
            source,
        });
    }

    return snapshotFromRecords(records);
}

function enforceSourceBaseline(snapshot, label) {
    assert.equal(
        snapshot.digest,
        sourceBaseline.expectedDigest,
        `${label}: selected Aura source drifted. Review the change, then update source-baseline.json and the documented digest intentionally.`,
    );
}

async function writeSourceSnapshot(snapshot, destination) {
    for (const record of snapshot.records) {
        const outputPath = path.join(destination, record.path);

        await fs.mkdir(path.dirname(outputPath), { recursive: true });
        await fs.writeFile(outputPath, record.source);
        const writtenSource = await fs.readFile(outputPath);
        assert.ok(writtenSource.equals(record.source), `Copied source bytes must match the authenticated snapshot: ${record.path}`);
    }
}

async function copyAuraSources(destination, label) {
    const snapshot = await inspectAuraSources(repositoryRoot);
    enforceSourceBaseline(snapshot, label);
    await writeSourceSnapshot(snapshot, destination);
    await copyFile(
        path.join(fixtureDirectory, 'semantic-probe.blade.php'),
        path.join(destination, 'fixtures/semantic-probe.blade.php'),
    );

    return snapshot;
}

async function assertDocumentedSourceBaseline() {
    const documentation = await fs.readFile(documentationPath, 'utf8');
    const matches = [...documentation.matchAll(/Audited source SHA-256: `([a-f0-9]{64})`\./g)];

    assert.equal(matches.length, 1, 'Frontend compatibility docs must contain exactly one audited source digest');
    assert.equal(matches[0][1], sourceBaseline.expectedDigest, 'Documented source digest must match source-baseline.json');
}

function assertDuplicateJsonMembersAreRejected() {
    const duplicateCases = [
        [
            'ordinary source path',
            '[{"path":"first.blade.php","path":"second.blade.php","classes":["fixture"]}]',
            /Duplicate JSON member name "path"/,
        ],
        [
            'ordinary baseline algorithm',
            '{"algorithm":"sha256","algorithm":"sha512"}',
            /Duplicate JSON member name "algorithm"/,
        ],
        [
            'escaped-equivalent member',
            '[{"path":"first.blade.php","\\u0070ath":"second.blade.php","classes":["fixture"]}]',
            /Duplicate JSON member name "path"/,
        ],
        [
            'nested object inside an array',
            '[{"path":"fixture.blade.php","classes":[{"name":"first","name":"second"}]}]',
            /Duplicate JSON member name "name"/,
        ],
    ];

    for (const [label, json, pattern] of duplicateCases) {
        assert.throws(
            () => parseStrictJson(json, `${label} duplicate self-test`),
            pattern,
            `${label} duplicate must be rejected before object construction`,
        );
    }

    console.log('PASS strict JSON rejects duplicate path, algorithm, escaped, and nested members');
}

function assertMalformedUnicodeIsRejected() {
    const loneHighSurrogates = ['\uD800', '\uD801'];
    const loneLowSurrogate = '\uDC00';

    assert.equal(
        Buffer.from(loneHighSurrogates[0], 'utf8').toString('hex'),
        Buffer.from(loneHighSurrogates[1], 'utf8').toString('hex'),
        'Regression setup must reproduce replacement-byte collapse for U+D800 and U+D801',
    );

    for (const surrogate of [...loneHighSurrogates, loneLowSurrogate]) {
        const pathManifest = cloneSourceManifest();
        pathManifest[0].path = surrogate;
        assert.throws(() => validateSourceManifest(pathManifest), /well-formed Unicode/, 'Malformed manifest path must fail');

        const classManifest = cloneSourceManifest();
        classManifest[0].classes[0] = surrogate;
        assert.throws(() => validateSourceManifest(classManifest), /well-formed Unicode/, 'Malformed manifest class must fail');

        for (const field of ['algorithm', 'expectedDigest']) {
            const baseline = { ...sourceBaseline, [field]: surrogate };
            assert.throws(() => validateSourceBaseline(baseline), /well-formed Unicode/, `Malformed baseline ${field} must fail`);
        }

        assert.throws(
            () => calculateSourceDigest([{ path: surrogate, classes: ['fixture'], source: Buffer.from('fixture') }]),
            /well-formed Unicode/,
            'Malformed digest path must fail before UTF-8 encoding',
        );
        assert.throws(
            () => calculateSourceDigest([{ path: 'fixture', classes: [surrogate], source: Buffer.from('fixture') }]),
            /well-formed Unicode/,
            'Malformed digest class must fail before UTF-8 encoding',
        );
    }

    for (const escapedSurrogate of ['\\uD800', '\\uD801', '\\uDC00']) {
        assert.throws(
            () => parseStrictJson(`{"algorithm":"${escapedSurrogate}"}`, 'Unicode JSON self-test'),
            /well-formed Unicode/,
            'Strict JSON must reject escaped lone surrogates',
        );
    }

    const pairedSurrogate = parseStrictJson('{"value":"\\uD83D\\uDE00"}', 'Paired Unicode JSON self-test');
    assert.equal(pairedSurrogate.value, '😀', 'Strict JSON must preserve valid surrogate pairs');

    console.log('PASS malformed path, class, algorithm, and digest Unicode is rejected before UTF-8 encoding');
}

async function assertCanonicalSourcePathsAreEnforced() {
    const fixtureManifest = (...paths) => paths.map((sourcePath) => ({
        path: sourcePath,
        classes: ['fixture'],
    }));

    const aliasRoot = path.join(temporaryRoot, 'source-path-alias');
    await fs.mkdir(aliasRoot, { recursive: true });
    let aliasPath;

    if (process.platform === 'win32') {
        const realDirectory = path.join(aliasRoot, 'source');
        await fs.mkdir(realDirectory);
        await fs.writeFile(path.join(realDirectory, 'source.blade.php'), 'fixture');
        await fs.symlink(realDirectory, path.join(aliasRoot, 'alias'), 'junction');
        aliasPath = 'alias/source.blade.php';
    } else {
        await fs.writeFile(path.join(aliasRoot, 'source.blade.php'), 'fixture');
        await fs.symlink('source.blade.php', path.join(aliasRoot, 'alias.blade.php'));
        aliasPath = 'alias.blade.php';
    }

    await assert.rejects(
        inspectAuraSources(aliasRoot, fixtureManifest(aliasPath)),
        /canonical repository-relative realpath/,
        'Symlink aliases must not count as canonical source records',
    );

    const hardlinkRoot = path.join(temporaryRoot, 'source-path-hardlink');
    await fs.mkdir(hardlinkRoot, { recursive: true });
    await fs.writeFile(path.join(hardlinkRoot, 'source.blade.php'), 'fixture');
    await fs.link(path.join(hardlinkRoot, 'source.blade.php'), path.join(hardlinkRoot, 'alias.blade.php'));
    await assert.rejects(
        inspectAuraSources(hardlinkRoot, fixtureManifest('source.blade.php', 'alias.blade.php')),
        /file identity/,
        'Hardlink aliases must not count as distinct source records',
    );

    const caseRoot = path.join(temporaryRoot, 'source-path-case');
    await fs.mkdir(caseRoot, { recursive: true });
    await fs.writeFile(path.join(caseRoot, 'Case.blade.php'), 'fixture');
    await fs.writeFile(path.join(caseRoot, 'case.blade.php'), 'fixture');
    await assert.rejects(
        inspectAuraSources(caseRoot, fixtureManifest('Case.blade.php', 'case.blade.php')),
        /case-insensitive path identity|canonical repository-relative realpath|file identity/,
        'Case variants must not count as distinct source records on any filesystem',
    );

    const escapeParent = path.join(temporaryRoot, 'source-path-escape');
    const escapeRoot = path.join(escapeParent, 'repository');
    await fs.mkdir(escapeRoot, { recursive: true });
    let escapingAliasPath;

    if (process.platform === 'win32') {
        const outsideDirectory = path.join(escapeParent, 'outside');
        await fs.mkdir(outsideDirectory);
        await fs.writeFile(path.join(outsideDirectory, 'outside.blade.php'), 'fixture');
        await fs.symlink(outsideDirectory, path.join(escapeRoot, 'escape'), 'junction');
        escapingAliasPath = 'escape/outside.blade.php';
    } else {
        await fs.writeFile(path.join(escapeParent, 'outside.blade.php'), 'fixture');
        await fs.symlink('../outside.blade.php', path.join(escapeRoot, 'escape.blade.php'));
        escapingAliasPath = 'escape.blade.php';
    }

    await assert.rejects(
        inspectAuraSources(escapeRoot, fixtureManifest(escapingAliasPath)),
        /escapes source root/,
        'Symlink escapes must be rejected after realpath resolution',
    );
    await assert.rejects(
        inspectAuraSources(escapeRoot, fixtureManifest('../outside.blade.php')),
        /cannot traverse upward/,
        'Lexical source escapes must be rejected before resolution',
    );

    console.log('PASS canonical realpaths reject symlink, hardlink, case, and escape aliases');
}

function legacyAmbiguousDigest(records) {
    const digest = crypto.createHash('sha256');

    for (const record of records) {
        digest.update(record.path);
        digest.update('\0');
        digest.update(record.source);
    }

    return digest.digest('hex');
}

function assertCanonicalRecordFraming() {
    const leftRecords = [
        { path: 'a', classes: ['fixture'], source: Buffer.from('bc') },
        { path: 'd', classes: ['fixture'], source: Buffer.from('e') },
    ];
    const reframedRecords = [
        { path: 'a', classes: ['fixture'], source: Buffer.from('b') },
        { path: 'cd', classes: ['fixture'], source: Buffer.from('e') },
    ];

    assert.equal(
        legacyAmbiguousDigest(leftRecords),
        legacyAmbiguousDigest(reframedRecords),
        'Regression setup must reproduce the legacy record-boundary ambiguity',
    );
    assert.notEqual(
        calculateSourceDigest(leftRecords),
        calculateSourceDigest(reframedRecords),
        'Length-prefixed canonical records must reject boundary reframing',
    );

    console.log('PASS canonical source records reject legacy boundary reframing');
}

function cloneSourceManifest() {
    return sourceManifest.map((entry) => ({
        path: entry.path,
        classes: [...entry.classes],
    }));
}

async function assertClassMetadataDriftIsRejected() {
    const mutations = [];
    const replacedClass = cloneSourceManifest();
    replacedClass[0].classes[0] = 'antialiased';
    mutations.push(['class value', replacedClass]);

    const addedClass = cloneSourceManifest();
    addedClass[0].classes.push('antialiased');
    mutations.push(['class count', addedClass]);

    const reorderedClasses = cloneSourceManifest();
    [reorderedClasses[0].classes[0], reorderedClasses[0].classes[1]] = [
        reorderedClasses[0].classes[1],
        reorderedClasses[0].classes[0],
    ];
    mutations.push(['class order', reorderedClasses]);

    for (const [label, manifest] of mutations) {
        const snapshot = await inspectAuraSources(repositoryRoot, manifest);

        assert.notEqual(snapshot.digest, sourceBaseline.expectedDigest, `${label} mutation must change the source digest`);
        assert.throws(
            () => enforceSourceBaseline(snapshot, `${label} mutation self-test`),
            /selected Aura source drifted/,
            `${label} mutation must fail against the fixed baseline`,
        );
    }

    const extraManifestDatum = cloneSourceManifest();
    extraManifestDatum[0].description = 'unauthenticated metadata';
    assert.throws(
        () => validateSourceManifest(extraManifestDatum),
        /must contain exactly path and classes/,
        'Unexpected manifest metadata must be rejected instead of remaining unauthenticated',
    );

    console.log('PASS fixed source baseline rejects class value, count, order, and extra metadata mutations');
}

function assertExactSourceBytesAreAuthenticated() {
    const lfRecords = [{ path: 'fixture', classes: ['fixture'], source: Buffer.from('line\n') }];
    const crlfRecords = [{ path: 'fixture', classes: ['fixture'], source: Buffer.from('line\r\n') }];

    assert.notEqual(
        calculateSourceDigest(lfRecords),
        calculateSourceDigest(crlfRecords),
        'Source digest must authenticate exact bytes without line-ending normalization',
    );

    console.log('PASS canonical source records authenticate exact source bytes');
}

async function assertSourceDriftIsRejected() {
    const sourceRoot = path.join(temporaryRoot, 'source-drift-self-test');
    const sourceSnapshot = await inspectAuraSources(repositoryRoot);

    enforceSourceBaseline(sourceSnapshot, 'Drift self-test setup');
    await writeSourceSnapshot(sourceSnapshot, sourceRoot);
    const [modifiedSource] = await resolveSelectedSources(sourceRoot, [sourceManifest[0]]);
    await fs.appendFile(modifiedSource.sourcePath, '\n{{-- Frontend compatibility drift self-test. --}}\n');
    const driftedSnapshot = await inspectAuraSources(sourceRoot);

    assert.notEqual(driftedSnapshot.digest, sourceBaseline.expectedDigest, 'Self-test mutation must change the selected-source digest');
    assert.throws(
        () => enforceSourceBaseline(driftedSnapshot, 'Drift self-test'),
        /selected Aura source drifted/,
        'Modified selected source must fail against the fixed baseline',
    );

    console.log('PASS fixed source baseline rejects a modified selected source');
}

async function execute(command, args, cwd) {
    return execFileAsync(command, args, {
        cwd,
        maxBuffer: 20 * 1024 * 1024,
        windowsHide: true,
    });
}

async function expectFailure(command, args, cwd, pattern, label) {
    try {
        await execute(command, args, cwd);
    } catch (error) {
        const output = `${error.stdout ?? ''}\n${error.stderr ?? ''}\n${error.message}`;
        assert.match(output, pattern, `${label} failed for an unexpected reason`);
        console.log(`PASS ${label}`);

        return;
    }

    throw new Error(`${label} unexpectedly succeeded`);
}

async function buildTailwind3() {
    const directory = path.join(temporaryRoot, 'tailwind-3');

    await fs.mkdir(directory, { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.config.cjs'), path.join(directory, 'tailwind.config.cjs'));
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.css'), path.join(directory, 'input.css'));
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(directory, 'token-contract.css'));
    await copyAuraSources(path.join(directory, 'aura-source'), 'Tailwind 3 source lane');

    const outputPath = path.join(directory, 'output.css');
    await execute(
        process.execPath,
        [tailwindCliPath, '-c', 'tailwind.config.cjs', '-i', 'input.css', '-o', outputPath, '--minify'],
        directory,
    );
    const result = assertCssContract(outputPath, 3);

    console.log(`PASS Tailwind 3.4.19: ${result.assertionCount} parsed assertions (${result.bytes} bytes)`);
}

async function prepareTailwind4() {
    const directory = path.join(temporaryRoot, 'tailwind-4');

    await fs.cp(path.join(fixtureDirectory, 'v4'), directory, { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(directory, 'resources/css/token-contract.css'));
    await copyAuraSources(path.join(directory, 'aura-source'), 'Tailwind 4 source lane');

    await execute(process.execPath, [npmCliPath, 'ci', '--ignore-scripts', '--no-audit', '--no-fund'], directory);

    return directory;
}

async function buildTailwind4(directory) {
    await execute(process.execPath, [npmCliPath, 'run', 'build'], directory);

    const manifest = JSON.parse(await fs.readFile(path.join(directory, 'dist/manifest.json'), 'utf8'));
    const entrypoint = manifest['index.html'];
    const cssFile = entrypoint?.css?.[0]
        ?? (entrypoint?.file?.endsWith('.css') ? entrypoint.file : null);
    assert.ok(cssFile, 'Vite manifest must expose the host CSS entrypoint');

    const outputPath = path.join(directory, 'dist', cssFile);
    const result = assertCssContract(outputPath, 4);

    console.log(`PASS Tailwind 4.3.3 + Vite 8.2.1: ${result.assertionCount} parsed assertions (${result.bytes} bytes)`);
}

async function checkNegativeBoundaries(tailwind4Directory) {
    const v3AgainstV4 = path.join(temporaryRoot, 'v3-against-v4');

    await fs.mkdir(path.join(v3AgainstV4, 'resources/css'), { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'v4/resources/css/app.css'), path.join(v3AgainstV4, 'resources/css/app.css'));
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(v3AgainstV4, 'resources/css/token-contract.css'));
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.config.cjs'), path.join(v3AgainstV4, 'tailwind.config.cjs'));
    await copyAuraSources(path.join(v3AgainstV4, 'aura-source'), 'Tailwind 3 negative source lane');

    await expectFailure(
        process.execPath,
        [tailwindCliPath, '-c', 'tailwind.config.cjs', '-i', 'resources/css/app.css', '-o', 'output.css'],
        v3AgainstV4,
        /Failed to find ['"]tailwindcss['"]/,
        'Tailwind 3 rejects the v4 CSS entrypoint',
    );

    const auraCss = await fs.readFile(path.join(repositoryRoot, 'resources/css/app.css'), 'utf8');
    const legacyExtract = await fs.readFile(path.join(fixtureDirectory, 'tailwind-v3-source-under-v4.css'), 'utf8');

    for (const directive of [
        "@import 'tailwindcss/base';",
        "@import 'tailwindcss/components';",
        "@import 'tailwindcss/utilities';",
        '@apply p-5 bg-white rounded-xl shadow-sm ring-1 ring-gray-950/10;',
    ]) {
        assert.ok(auraCss.includes(directive), `Aura app.css no longer contains the traced v3 extract: ${directive}`);
        assert.ok(legacyExtract.includes(directive), `Negative fixture is missing the traced v3 extract: ${directive}`);
    }

    await copyFile(
        path.join(fixtureDirectory, 'tailwind-v3-source-under-v4.css'),
        path.join(tailwind4Directory, 'resources/css/app.css'),
    );
    await expectFailure(
        process.execPath,
        [npmCliPath, 'run', 'build'],
        tailwind4Directory,
        /["']\.\/(?:base|components|utilities)["'] is not exported under the conditions? .*?["']style["']/s,
        'Tailwind 4 Vite rejects Aura\'s v3 CSS entrypoint',
    );
}

try {
    const sourceSnapshot = await inspectAuraSources(repositoryRoot);
    enforceSourceBaseline(sourceSnapshot, 'Pre-build source check');
    await assertDocumentedSourceBaseline();
    assertDuplicateJsonMembersAreRejected();
    assertMalformedUnicodeIsRejected();
    await assertCanonicalSourcePathsAreEnforced();
    assertCanonicalRecordFraming();
    assertExactSourceBytesAreAuthenticated();
    await assertClassMetadataDriftIsRejected();
    await assertSourceDriftIsRejected();
    console.log(`Aura source baseline: ${sourceSnapshot.count} files, sha256 ${sourceBaseline.expectedDigest}`);

    await buildTailwind3();
    const tailwind4Directory = await prepareTailwind4();
    await buildTailwind4(tailwind4Directory);
    await checkNegativeBoundaries(tailwind4Directory);
} finally {
    if (process.env.AURA_KEEP_FRONTEND_FIXTURE === '1') {
        console.log(`Kept fixture workspace: ${temporaryRoot}`);
    } else {
        await fs.rm(temporaryRoot, { recursive: true, force: true });
    }
}
