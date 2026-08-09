import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import { execFile } from 'node:child_process';
import { assertCssContract } from './assert-css-contract.mjs';

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
const sourceBaseline = JSON.parse(await fs.readFile(sourceBaselinePath, 'utf8'));
const sourceManifest = JSON.parse(await fs.readFile(sourceManifestPath, 'utf8'));

assert.ok(npmCliPath, 'Run this fixture through npm run test:frontend-compatibility.');
assert.deepEqual(
    Object.keys(sourceBaseline).sort(),
    ['algorithm', 'canonicalization', 'expectedDigest', 'manifest', 'sourceBytes'],
    'Source baseline must contain only supported contract settings',
);
assert.equal(sourceBaseline.manifest, path.basename(sourceManifestPath), 'Source baseline must name the selected-source manifest');
assert.equal(sourceBaseline.algorithm, 'sha256', 'Source baseline must use SHA-256');
assert.equal(sourceBaseline.canonicalization, sourceDigestCanonicalization, 'Source baseline must use canonical v2 records');
assert.equal(sourceBaseline.sourceBytes, 'exact', 'Source baseline must authenticate exact source bytes');
assert.match(sourceBaseline.expectedDigest, /^[a-f0-9]{64}$/, 'Source baseline must contain a SHA-256 digest');

async function copyFile(source, destination) {
    await fs.mkdir(path.dirname(destination), { recursive: true });
    await fs.copyFile(source, destination);
}

function resolveSelectedSource(sourceRoot, relativePath) {
    const resolvedRoot = path.resolve(sourceRoot);
    const sourcePath = path.resolve(resolvedRoot, relativePath);

    assert.ok(sourcePath.startsWith(`${resolvedRoot}${path.sep}`), `Unsafe source path: ${relativePath}`);

    return sourcePath;
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

function updateLengthPrefixed(digest, value) {
    const bytes = Buffer.isBuffer(value) ? value : Buffer.from(value, 'utf8');

    digest.update(unsigned64(bytes.length));
    digest.update(bytes);
}

function calculateSourceDigest(records) {
    const digest = crypto.createHash('sha256');

    updateLengthPrefixed(digest, sourceDigestCanonicalization);
    digest.update(unsigned64(records.length));

    for (const record of records) {
        updateLengthPrefixed(digest, record.path);
        digest.update(unsigned64(record.classes.length));

        for (const className of record.classes) {
            updateLengthPrefixed(digest, className);
        }

        updateLengthPrefixed(digest, record.source);
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

    for (const entry of manifest) {
        const sourcePath = resolveSelectedSource(sourceRoot, entry.path);
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
    const modifiedSource = resolveSelectedSource(sourceRoot, sourceManifest[0].path);
    await fs.appendFile(modifiedSource, '\n{{-- Frontend compatibility drift self-test. --}}\n');
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
