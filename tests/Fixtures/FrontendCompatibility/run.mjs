import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import { execFile } from 'node:child_process';
import { assertCssContract } from './assert-css-contract.mjs';
import {
    assertWellFormedUnicode,
    parseStrictJson,
    readStrictJsonFile,
    STRICT_JSON_MAX_BYTES,
    STRICT_JSON_MAX_DEPTH,
} from './strict-json.mjs';

const execFileAsync = promisify(execFile);
const fixtureDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(fixtureDirectory, '../../..');
const documentationPath = path.join(repositoryRoot, 'docs/frontend-compatibility.md');
const outputBaselinePath = path.join(fixtureDirectory, 'output-baseline.json');
const productionOutputBaselinePath = path.join(fixtureDirectory, 'production-output-baseline.json');
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
const outputBaseline = await readStrictJsonFile(outputBaselinePath, 'Frontend output baseline');
const productionOutputBaseline = await readStrictJsonFile(productionOutputBaselinePath, 'Production output baseline');
const sourceBaseline = await readStrictJsonFile(sourceBaselinePath, 'Frontend source baseline');
const sourceManifest = await readStrictJsonFile(sourceManifestPath, 'Frontend source manifest');
const protectedSourceCaptures = new Set();

assert.ok(npmCliPath, 'Run this fixture through npm run test:frontend-compatibility.');
validateOutputBaseline(outputBaseline);
validateProductionOutputBaseline(productionOutputBaseline);
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

function isAtOrWithinRoot(rootPath, candidatePath) {
    return path.resolve(rootPath) === path.resolve(candidatePath)
        || isWithinRoot(path.resolve(rootPath), path.resolve(candidatePath));
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

async function resolveGeneratedOutput(outputRoot, outputPath, label) {
    assert.ok(typeof outputPath === 'string' && outputPath.length > 0, `${label} must be a non-empty string`);
    assert.match(outputPath, /^[A-Za-z0-9._/-]+$/, `${label} must use portable ASCII characters`);
    assert.equal(path.posix.normalize(outputPath), outputPath, `${label} must be canonical`);
    assert.equal(path.posix.isAbsolute(outputPath), false, `${label} must be relative`);
    assert.equal(outputPath.split('/').includes('..'), false, `${label} cannot traverse upward`);

    const realOutputRoot = await fs.realpath(outputRoot);
    const requestedPath = path.resolve(realOutputRoot, ...outputPath.split('/'));

    assert.ok(isWithinRoot(realOutputRoot, requestedPath), `${label} escapes its output root`);

    const realOutputPath = await fs.realpath(requestedPath);

    assert.ok(isWithinRoot(realOutputRoot, realOutputPath), `${label} resolves outside its output root`);
    assert.equal(
        toRepositoryRelativePath(realOutputRoot, realOutputPath),
        outputPath,
        `${label} must equal its canonical output-relative realpath`,
    );

    const stats = await fs.stat(realOutputPath);

    assert.ok(stats.isFile(), `${label} must resolve to a regular file`);

    return realOutputPath;
}

function validateOutputBaseline(baseline) {
    assert.ok(baseline && typeof baseline === 'object' && ! Array.isArray(baseline), 'Output baseline must be an object');
    assert.deepEqual(
        Object.keys(baseline).sort(),
        ['algorithm', 'tailwind3', 'tailwind4'],
        'Output baseline must contain only supported contract settings',
    );
    assert.equal(baseline.algorithm, 'sha256', 'Output baseline must use SHA-256');

    for (const lane of ['tailwind3', 'tailwind4']) {
        const expectation = baseline[lane];

        assert.ok(expectation && typeof expectation === 'object' && ! Array.isArray(expectation), `${lane} output baseline must be an object`);
        assert.deepEqual(
            Object.keys(expectation).sort(),
            ['assertionCount', 'bytes', 'sha256'],
            `${lane} output baseline must contain exactly assertionCount, bytes, and sha256`,
        );
        assert.ok(Number.isSafeInteger(expectation.assertionCount) && expectation.assertionCount > 0, `${lane} assertion count must be a positive safe integer`);
        assert.ok(Number.isSafeInteger(expectation.bytes) && expectation.bytes > 0, `${lane} byte count must be a positive safe integer`);
        assert.match(expectation.sha256, /^[a-f0-9]{64}$/, `${lane} output baseline must contain a SHA-256 digest`);
    }
}

function validateProductionOutputBaseline(baseline) {
    assert.ok(baseline && typeof baseline === 'object' && ! Array.isArray(baseline), 'Production output baseline must be an object');
    assert.deepEqual(
        Object.keys(baseline).sort(),
        ['algorithm', 'files', 'root'],
        'Production output baseline must contain exactly algorithm, files, and root',
    );
    assert.equal(baseline.algorithm, 'sha256', 'Production output baseline must use SHA-256');
    assert.equal(baseline.root, 'resources/dist', 'Production output baseline must pin resources/dist');
    assert.ok(Array.isArray(baseline.files) && baseline.files.length > 0, 'Production output baseline must pin output files');

    const paths = new Set();

    for (const [index, file] of baseline.files.entries()) {
        assert.ok(file && typeof file === 'object' && ! Array.isArray(file), `Production output record ${index} must be an object`);
        assert.deepEqual(
            Object.keys(file).sort(),
            ['bytes', 'path', 'sha256'],
            `Production output record ${index} must contain exactly bytes, path, and sha256`,
        );
        assert.ok(typeof file.path === 'string' && file.path.length > 0, `Production output record ${index} needs a path`);
        assertWellFormedUnicode(file.path, `Production output path ${index}`);
        assert.match(file.path, /^[A-Za-z0-9._/-]+$/, `Production output path must use portable ASCII: ${file.path}`);
        assert.equal(path.posix.normalize(file.path), file.path, `Production output path must be canonical: ${file.path}`);
        assert.ok(file.path.startsWith(`${baseline.root}/`), `Production output path must remain under ${baseline.root}: ${file.path}`);
        assert.equal(file.path.split('/').includes('..'), false, `Production output path cannot traverse upward: ${file.path}`);
        assert.equal(paths.has(file.path), false, `Production output path must be unique: ${file.path}`);
        assert.ok(Number.isSafeInteger(file.bytes) && file.bytes > 0, `${file.path} byte count must be a positive safe integer`);
        assert.match(file.sha256, /^[a-f0-9]{64}$/, `${file.path} must contain a SHA-256 digest`);
        paths.add(file.path);
    }

    assert.deepEqual(
        [...paths],
        [...paths].sort((left, right) => left.localeCompare(right)),
        'Production output baseline paths must use stable lexical order',
    );
}

function enforceOutputBaseline(result, lane, label) {
    const expectation = outputBaseline[lane];

    assert.deepEqual(
        Object.entries(result).sort(([left], [right]) => left.localeCompare(right)),
        Object.entries(expectation).sort(([left], [right]) => left.localeCompare(right)),
        `${label}: compiler output drifted. Review the generated CSS, then update output-baseline.json intentionally.`,
    );
}

function productionOutputRecord(outputPath, source) {
    return {
        path: outputPath,
        bytes: source.length,
        sha256: crypto.createHash('sha256').update(source).digest('hex'),
    };
}

function enforceProductionOutputBaseline(records, label) {
    assert.deepEqual(
        records.map((record) => [record.path, record.bytes, record.sha256]),
        productionOutputBaseline.files.map((record) => [record.path, record.bytes, record.sha256]),
        `${label}: committed production output drifted. Review resources/dist, then update production-output-baseline.json intentionally.`,
    );
}

async function listWorkingTreeOutputPaths(directory) {
    const stats = await fs.lstat(directory);
    assert.ok(stats.isDirectory() && ! stats.isSymbolicLink(), `Production output directory must be a real directory: ${directory}`);

    const files = [];

    for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
        const entryPath = path.join(directory, entry.name);
        const entryStats = await fs.lstat(entryPath);

        assert.equal(entryStats.isSymbolicLink(), false, `Production output cannot contain symlinks: ${entryPath}`);

        if (entryStats.isDirectory()) {
            files.push(...await listWorkingTreeOutputPaths(entryPath));

            continue;
        }

        assert.ok(entryStats.isFile(), `Production output must contain only regular files: ${entryPath}`);
        files.push(path.relative(repositoryRoot, entryPath).split(path.sep).join('/'));
    }

    return files.sort((left, right) => left.localeCompare(right));
}

async function inspectWorkingTreeProductionOutputs() {
    const outputRoot = path.join(repositoryRoot, ...productionOutputBaseline.root.split('/'));
    const paths = await listWorkingTreeOutputPaths(outputRoot);

    return Promise.all(paths.map(async (outputPath) => productionOutputRecord(
        outputPath,
        await fs.readFile(path.join(repositoryRoot, ...outputPath.split('/'))),
    )));
}

async function inspectIndexProductionOutputs() {
    const { stdout } = await execFileAsync(
        'git',
        ['ls-files', '--stage', '-z', '--', productionOutputBaseline.root],
        {
            cwd: repositoryRoot,
            encoding: 'buffer',
            maxBuffer: 2 * 1024 * 1024,
            windowsHide: true,
        },
    );
    const entries = stdout.toString('utf8').split('\0').filter(Boolean).map((record) => {
        const match = record.match(/^(\d{6}) ([a-f0-9]{40,64}) (\d+)\t(.+)$/);

        assert.ok(match, `Unexpected Git index record for production output: ${record}`);
        assert.equal(match[1], '100644', `Production output must use regular non-executable Git mode: ${match[4]}`);
        assert.equal(match[3], '0', `Production output cannot have unresolved index stages: ${match[4]}`);

        return { path: match[4] };
    });

    entries.sort((left, right) => left.path.localeCompare(right.path));

    return Promise.all(entries.map(async (entry) => {
        const { stdout: source } = await execFileAsync(
            'git',
            ['cat-file', 'blob', `:${entry.path}`],
            {
                cwd: repositoryRoot,
                encoding: 'buffer',
                maxBuffer: 20 * 1024 * 1024,
                windowsHide: true,
            },
        );

        return productionOutputRecord(entry.path, source);
    }));
}

async function assertProductionOutputsPinned(label) {
    enforceProductionOutputBaseline(await inspectWorkingTreeProductionOutputs(), `${label} working tree`);
    enforceProductionOutputBaseline(await inspectIndexProductionOutputs(), `${label} Git index`);
}

function assertProductionOutputDriftIsRejected() {
    const pinned = productionOutputBaseline.files.map((file) => ({ ...file }));

    enforceProductionOutputBaseline(pinned, 'Production output self-test');

    for (const field of ['path', 'bytes', 'sha256']) {
        const drifted = pinned.map((file) => ({ ...file }));

        drifted[0][field] = field === 'bytes'
            ? drifted[0][field] + 1
            : `drift-${drifted[0][field]}`;

        assert.throws(
            () => enforceProductionOutputBaseline(drifted, `${field} production output self-test`),
            /committed production output drifted/,
            `Production output ${field} drift must be rejected`,
        );
    }

    console.log('PASS external production output baseline pins worktree and index paths, bytes, and SHA-256 digests');
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
        assert.match(
            entry.path,
            /^[A-Za-z0-9._/-]+$/,
            `Source manifest path must use portable ASCII characters: ${entry.path}`,
        );

        for (const segment of entry.path.split('/')) {
            assert.doesNotMatch(segment, /\.$/, `Source manifest path segment cannot end with a dot: ${entry.path}`);
            assert.doesNotMatch(
                segment.split('.')[0],
                /^(?:aux|com[1-9]|con|lpt[1-9]|nul|prn)$/i,
                `Source manifest path cannot use a reserved portable filename: ${entry.path}`,
            );
        }

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

function compilerSourceManifest() {
    return [
        ...cloneSourceManifest(),
        {
            path: 'fixtures/semantic-probe.blade.php',
            classes: [
                'font-sans',
                'bg-aura-background',
                'bg-aura-panel/80',
                'text-aura-muted',
                'text-aura-success',
                'text-aura-warning',
                'text-aura-danger',
            ],
        },
    ];
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

function immutableManifestFromSnapshot(snapshot) {
    const manifest = snapshot.records.map((record) => Object.freeze({
        path: record.path,
        classes: Object.freeze([...record.classes]),
    }));

    return Object.freeze(manifest);
}

function sourceCaptureDirectories(sourceRoot, manifest) {
    const directories = new Set([sourceRoot]);

    for (const entry of manifest) {
        let directory = path.dirname(path.join(sourceRoot, entry.path));

        while (directory !== sourceRoot) {
            directories.add(directory);
            directory = path.dirname(directory);
        }
    }

    return [...directories].sort((left, right) => left.length - right.length || left.localeCompare(right));
}

function filesystemIdentity(stats) {
    return `${stats.dev}:${stats.ino}`;
}

function permissionMode(stats) {
    return Number(stats.mode & 0o777n);
}

async function captureFilesystemEntry(sourceRoot, entryPath, kind, protectedMode) {
    const absolutePath = path.resolve(entryPath);

    assert.ok(isAtOrWithinRoot(sourceRoot, absolutePath), `Captured ${kind} escapes source root: ${absolutePath}`);

    const stats = await fs.lstat(absolutePath, { bigint: true });

    assert.equal(stats.isSymbolicLink(), false, `Captured ${kind} cannot be a symlink: ${absolutePath}`);
    assert.ok(
        kind === 'directory' ? stats.isDirectory() : stats.isFile(),
        `Captured ${kind} has an unexpected filesystem type: ${absolutePath}`,
    );

    return Object.freeze({
        absolutePath,
        identity: filesystemIdentity(stats),
        initialMode: permissionMode(stats),
        kind,
        linkCount: stats.nlink.toString(),
        protectedMode,
        relativePath: toRepositoryRelativePath(sourceRoot, absolutePath) || '.',
    });
}

async function captureSourceFilesystem(sourceRoot, manifest) {
    const absoluteRoot = path.resolve(sourceRoot);
    const directories = [];
    const files = [];

    for (const directory of sourceCaptureDirectories(absoluteRoot, manifest)) {
        directories.push(await captureFilesystemEntry(absoluteRoot, directory, 'directory', 0o555));
    }

    for (const entry of manifest) {
        files.push(await captureFilesystemEntry(
            absoluteRoot,
            path.join(absoluteRoot, ...entry.path.split('/')),
            'file',
            0o444,
        ));
    }

    return Object.freeze({
        directories: Object.freeze(directories),
        files: Object.freeze(files),
        root: directories[0],
    });
}

async function assertCaptureEntry(capture, entry, modeKey, label) {
    assert.ok(
        entry === capture.filesystem.root
            ? path.resolve(entry.absolutePath) === path.resolve(capture.sourceRoot)
            : isWithinRoot(capture.sourceRoot, entry.absolutePath),
        `${label}: captured ${entry.kind} path escaped its source root: ${entry.relativePath}`,
    );

    let stats;

    try {
        stats = await fs.lstat(entry.absolutePath, { bigint: true });
    } catch (error) {
        assert.fail(`${label}: captured ${entry.kind} is unavailable: ${entry.relativePath} (${error.code ?? error.message})`);
    }

    assert.equal(stats.isSymbolicLink(), false, `${label}: captured ${entry.kind} became a symlink: ${entry.relativePath}`);
    assert.ok(
        entry.kind === 'directory' ? stats.isDirectory() : stats.isFile(),
        `${label}: captured ${entry.kind} changed filesystem type: ${entry.relativePath}`,
    );
    assert.equal(
        filesystemIdentity(stats),
        entry.identity,
        `${label}: captured ${entry.kind} identity drifted: ${entry.relativePath}`,
    );
    assert.equal(
        stats.nlink.toString(),
        entry.linkCount,
        `${label}: captured ${entry.kind} link count drifted: ${entry.relativePath}`,
    );

    if (modeKey !== null && process.platform !== 'win32') {
        assert.equal(
            permissionMode(stats),
            entry[modeKey],
            `${label}: captured ${entry.kind} mode drifted: ${entry.relativePath}`,
        );
    }
}

async function assertSourceCaptureFilesystem(capture, modeKey, label) {
    await assertCaptureEntry(capture, capture.filesystem.root, modeKey, label);

    for (const entry of capture.filesystem.directories.slice(1)) {
        await assertCaptureEntry(capture, entry, modeKey, label);
    }

    for (const entry of capture.filesystem.files) {
        await assertCaptureEntry(capture, entry, modeKey, label);
    }
}

async function chmodCapturedEntry(capture, entry, mode, label) {
    await assertCaptureEntry(capture, entry, null, label);
    await fs.chmod(entry.absolutePath, mode);
}

async function protectSourceCapture(capture) {
    protectedSourceCaptures.add(capture);
    await assertSourceCaptureFilesystem(capture, 'initialMode', 'Source capture pre-protection check');

    for (const entry of capture.filesystem.files) {
        await chmodCapturedEntry(capture, entry, entry.protectedMode, 'Source capture file protection');
    }

    for (const entry of [...capture.filesystem.directories].reverse()) {
        await chmodCapturedEntry(capture, entry, entry.protectedMode, 'Source capture directory protection');
    }

    await assertSourceCaptureFilesystem(capture, 'protectedMode', 'Source capture post-protection check');
}

async function releaseSourceCapture(capture) {
    if (! protectedSourceCaptures.has(capture)) {
        return;
    }

    await assertSourceCaptureFilesystem(capture, 'protectedMode', 'Source capture pre-cleanup check');

    for (const entry of capture.filesystem.files) {
        await chmodCapturedEntry(capture, entry, entry.initialMode, 'Source capture file cleanup');
    }

    for (const entry of [...capture.filesystem.directories].reverse()) {
        await chmodCapturedEntry(capture, entry, entry.initialMode, 'Source capture directory cleanup');
    }

    await assertSourceCaptureFilesystem(capture, 'initialMode', 'Source capture post-cleanup check');
    protectedSourceCaptures.delete(capture);
}

async function prepareCaptureDirectoriesForRemoval(capture) {
    const unsafeDirectories = [];

    for (const entry of capture.filesystem.directories) {
        const isUnderUnsafeDirectory = unsafeDirectories.some((unsafePath) => (
            entry.absolutePath === unsafePath || isWithinRoot(unsafePath, entry.absolutePath)
        ));

        if (isUnderUnsafeDirectory) {
            continue;
        }

        try {
            await assertCaptureEntry(capture, entry, null, 'Source capture removal preparation');
            await fs.chmod(entry.absolutePath, entry.initialMode);
        } catch {
            unsafeDirectories.push(entry.absolutePath);
        }
    }
}

async function assertSourceCaptureUnchanged(capture, label) {
    await assertSourceCaptureFilesystem(capture, 'protectedMode', `${label} persistent identity check`);
    await assertSourceCaptureContentUnchanged(capture, label);
    await assertSourceCaptureFilesystem(capture, 'protectedMode', `${label} final persistent identity check`);
}

async function assertSourceCaptureContentUnchanged(capture, label) {
    const currentSnapshot = await inspectAuraSources(capture.sourceRoot, capture.manifest);

    assert.equal(currentSnapshot.count, capture.count, `${label}: compiler source record count changed after capture`);
    assert.equal(currentSnapshot.digest, capture.digest, `${label}: compiler source bytes or metadata changed after capture`);
}

async function copyAuraSources(destination, label) {
    const authenticatedSnapshot = await inspectAuraSources(repositoryRoot);
    enforceSourceBaseline(authenticatedSnapshot, label);
    await writeSourceSnapshot(authenticatedSnapshot, destination);
    await copyFile(
        path.join(fixtureDirectory, 'semantic-probe.blade.php'),
        path.join(destination, 'fixtures/semantic-probe.blade.php'),
    );
    const compilerSnapshot = await inspectAuraSources(destination, compilerSourceManifest());
    const manifest = immutableManifestFromSnapshot(compilerSnapshot);
    const filesystem = await captureSourceFilesystem(destination, manifest);
    const capture = Object.freeze({
        count: compilerSnapshot.count,
        digest: compilerSnapshot.digest,
        filesystem,
        manifest,
        sourceRoot: path.resolve(destination),
    });

    await protectSourceCapture(capture);

    return capture;
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

async function assertStrictJsonLimitsAreEnforced() {
    const deepDuplicate = `${'['.repeat(STRICT_JSON_MAX_DEPTH - 1)}{"member":1,"member":2}${']'.repeat(STRICT_JSON_MAX_DEPTH - 1)}`;

    assert.throws(
        () => parseStrictJson(deepDuplicate, 'Deep duplicate self-test'),
        /Duplicate JSON member name "member"/,
        'Duplicate detection must remain active at the deepest accepted nesting level',
    );

    const hostileDepth = `${'['.repeat(5000)}{"member":1,"member":2}${']'.repeat(5000)}`;
    assert.throws(
        () => parseStrictJson(hostileDepth, 'Hostile depth self-test'),
        (error) => {
            assert.equal(error?.constructor, SyntaxError, 'Excessive nesting must fail with SyntaxError, not RangeError');
            assert.equal(
                error.message,
                `Hostile depth self-test at character ${STRICT_JSON_MAX_DEPTH}: Maximum nesting depth of ${STRICT_JSON_MAX_DEPTH} exceeded`,
                'Excessive nesting must have a deterministic error',
            );

            return true;
        },
    );

    const maximumJson = JSON.stringify('x'.repeat(STRICT_JSON_MAX_BYTES - 2));
    assert.equal(
        parseStrictJson(maximumJson, 'Maximum byte boundary self-test').length,
        STRICT_JSON_MAX_BYTES - 2,
        'A JSON document exactly at the byte limit must remain accepted',
    );

    const oversizedJson = JSON.stringify('x'.repeat(STRICT_JSON_MAX_BYTES));
    const expectedByteError = `exceeds maximum byte length of ${STRICT_JSON_MAX_BYTES} bytes`;

    assert.throws(
        () => parseStrictJson(oversizedJson, 'Oversized string self-test'),
        new RegExp(expectedByteError),
        'Direct strict JSON parsing must enforce the byte limit',
    );
    assert.throws(
        () => parseStrictJson(JSON.stringify('\u00e9'.repeat((STRICT_JSON_MAX_BYTES / 2) + 1)), 'UTF-8 byte self-test'),
        new RegExp(expectedByteError),
        'The limit must count encoded UTF-8 bytes rather than JavaScript code units',
    );

    const oversizedPath = path.join(temporaryRoot, 'strict-json-byte-boundary-self-test.json');
    await fs.writeFile(oversizedPath, maximumJson);
    assert.equal(
        (await readStrictJsonFile(oversizedPath, 'Maximum file boundary self-test')).length,
        STRICT_JSON_MAX_BYTES - 2,
        'A strict JSON file exactly at the byte limit must remain accepted',
    );
    await fs.writeFile(oversizedPath, oversizedJson);
    await assert.rejects(
        readStrictJsonFile(oversizedPath, 'Oversized file self-test'),
        new RegExp(expectedByteError),
        'Strict JSON files must enforce the byte limit before decoding',
    );

    console.log(`PASS strict JSON enforces ${STRICT_JSON_MAX_BYTES} byte and ${STRICT_JSON_MAX_DEPTH} level limits`);
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

    for (const unicodePath of ['resources/caf\u00e9.blade.php', 'resources/cafe\u0301.blade.php']) {
        assert.throws(
            () => validateSourceManifest(fixtureManifest(unicodePath)),
            /portable ASCII characters/,
            'Unicode normalization variants must not enter manifest path identity',
        );
    }

    assert.throws(
        () => validateSourceManifest(fixtureManifest('resources/CON.blade.php')),
        /reserved portable filename/,
        'Windows device names must not enter cross-platform manifest identity',
    );

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

    console.log('PASS portable canonical paths reject Unicode, symlink, hardlink, case, and escape aliases');
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

function assertOutputContractIsPinned() {
    enforceOutputBaseline({ ...outputBaseline.tailwind3 }, 'tailwind3', 'Output baseline self-test');

    for (const [field, value] of [
        ['assertionCount', outputBaseline.tailwind3.assertionCount + 1],
        ['bytes', outputBaseline.tailwind3.bytes + 1],
        [
            'sha256',
            `${outputBaseline.tailwind3.sha256.startsWith('0') ? '1' : '0'}${outputBaseline.tailwind3.sha256.slice(1)}`,
        ],
    ]) {
        assert.throws(
            () => enforceOutputBaseline(
                { ...outputBaseline.tailwind3, [field]: value },
                'tailwind3',
                `${field} mutation self-test`,
            ),
            /compiler output drifted/,
            `${field} output drift must be rejected`,
        );
    }

    console.log('PASS compiler output baseline pins semantic assertion, byte, and SHA-256 counts');
}

async function assertCapturedSourceMutationIsRejected() {
    const sourceRoot = path.join(temporaryRoot, 'post-compiler-mutation-self-test');
    const capture = await copyAuraSources(sourceRoot, 'Post-compiler mutation self-test');

    await releaseSourceCapture(capture);
    await fs.appendFile(
        path.join(sourceRoot, capture.manifest[0].path),
        '\n<div class="unauthenticated-compiler-input"></div>\n',
    );
    await assert.rejects(
        assertSourceCaptureContentUnchanged(capture, 'Post-compiler mutation self-test'),
        /compiler source bytes or metadata changed after capture/,
        'Mutation after the copy verification must fail the post-compiler rehash',
    );

    console.log('PASS post-compiler rehash rejects mutation after source capture');
}

async function reprotectCaptureForSelfTest(capture) {
    for (const entry of capture.filesystem.files) {
        await chmodCapturedEntry(capture, entry, entry.protectedMode, 'Persistent drift self-test file repair');
    }

    for (const entry of [...capture.filesystem.directories].reverse()) {
        await chmodCapturedEntry(capture, entry, entry.protectedMode, 'Persistent drift self-test directory repair');
    }
}

async function assertPersistentCaptureDriftIsRejected() {
    if (process.platform !== 'win32') {
        const modeRoot = path.join(temporaryRoot, 'persistent-mode-drift-self-test');
        const modeCapture = await copyAuraSources(modeRoot, 'Persistent mode drift self-test');
        const modeEntry = modeCapture.filesystem.files[0];

        try {
            await fs.chmod(modeEntry.absolutePath, 0o600);
            await assert.rejects(
                assertSourceCaptureUnchanged(modeCapture, 'Persistent mode drift self-test'),
                /mode drifted/,
                'Persistent compiler-source mode drift must fail its lane checkpoint',
            );
            await assert.rejects(
                releaseSourceCapture(modeCapture),
                /mode drifted/,
                'Cleanup must refuse a capture whose mode identity drifted',
            );
            await prepareCaptureDirectoriesForRemoval(modeCapture);
        } finally {
            if (protectedSourceCaptures.has(modeCapture)) {
                await reprotectCaptureForSelfTest(modeCapture);
                await releaseSourceCapture(modeCapture);
            }
        }
    }

    const aliasRoot = path.join(temporaryRoot, 'persistent-hardlink-alias-self-test');
    const aliasCapture = await copyAuraSources(aliasRoot, 'Persistent hardlink alias self-test');
    const aliasedEntry = aliasCapture.filesystem.files[0];
    const aliasPath = path.join(temporaryRoot, 'persistent-hardlink-alias');

    await fs.link(aliasedEntry.absolutePath, aliasPath);
    const aliasBeforeCleanup = await fs.lstat(aliasPath, { bigint: true });

    try {
        await assert.rejects(
            assertSourceCaptureUnchanged(aliasCapture, 'Persistent hardlink alias self-test'),
            /link count drifted/,
            'A persistent external hardlink to captured source must fail its lane checkpoint',
        );
        await assert.rejects(
            releaseSourceCapture(aliasCapture),
            /link count drifted/,
            'Cleanup must refuse captured source with a new external hardlink',
        );
        await prepareCaptureDirectoriesForRemoval(aliasCapture);
        const aliasAfterCleanup = await fs.lstat(aliasPath, { bigint: true });

        assert.equal(
            permissionMode(aliasAfterCleanup),
            permissionMode(aliasBeforeCleanup),
            'Rejected cleanup must not chmod an external hardlink to captured source',
        );
    } finally {
        if (protectedSourceCaptures.has(aliasCapture)) {
            await fs.unlink(aliasPath);
            await reprotectCaptureForSelfTest(aliasCapture);
            await releaseSourceCapture(aliasCapture);
        }
    }

    const hardlinkRoot = path.join(temporaryRoot, 'persistent-hardlink-drift-self-test');
    const hardlinkCapture = await copyAuraSources(hardlinkRoot, 'Persistent hardlink drift self-test');
    const replacedEntry = hardlinkCapture.filesystem.files[0];
    const parentEntry = hardlinkCapture.filesystem.directories.find(
        (entry) => entry.absolutePath === path.dirname(replacedEntry.absolutePath),
    );
    const originalPath = path.join(temporaryRoot, 'persistent-hardlink-original');
    const externalPath = path.join(temporaryRoot, 'persistent-hardlink-external');

    assert.ok(parentEntry, 'Persistent hardlink self-test must capture the selected file parent');
    await fs.writeFile(externalPath, await fs.readFile(replacedEntry.absolutePath));
    await fs.chmod(externalPath, 0o600);
    await fs.chmod(parentEntry.absolutePath, parentEntry.initialMode | 0o700);
    await fs.rename(replacedEntry.absolutePath, originalPath);
    await fs.link(externalPath, replacedEntry.absolutePath);
    await fs.chmod(parentEntry.absolutePath, parentEntry.protectedMode);
    const externalBeforeCleanup = await fs.lstat(externalPath, { bigint: true });

    try {
        await assert.rejects(
            assertSourceCaptureUnchanged(hardlinkCapture, 'Persistent hardlink drift self-test'),
            /identity drifted/,
            'Persistent hardlink replacement must fail its lane checkpoint even when bytes are identical',
        );
        await assert.rejects(
            releaseSourceCapture(hardlinkCapture),
            /identity drifted/,
            'Cleanup must refuse a hardlink-replaced capture',
        );
        await prepareCaptureDirectoriesForRemoval(hardlinkCapture);
        const externalAfterCleanup = await fs.lstat(externalPath, { bigint: true });

        assert.equal(
            permissionMode(externalAfterCleanup),
            permissionMode(externalBeforeCleanup),
            'Rejected cleanup must not chmod a hardlink replacement outside the capture',
        );
    } finally {
        if (protectedSourceCaptures.has(hardlinkCapture)) {
            await fs.chmod(parentEntry.absolutePath, parentEntry.initialMode | 0o700);
            await fs.unlink(replacedEntry.absolutePath);
            await fs.rename(originalPath, replacedEntry.absolutePath);
            await reprotectCaptureForSelfTest(hardlinkCapture);
            await releaseSourceCapture(hardlinkCapture);
        }
    }

    const swappedRoot = path.join(temporaryRoot, 'persistent-root-drift-self-test');
    const rootCapture = await copyAuraSources(swappedRoot, 'Persistent root drift self-test');
    const originalRoot = path.join(temporaryRoot, 'persistent-root-original');
    const externalRoot = path.join(temporaryRoot, 'persistent-root-external');
    const exactCopySnapshot = await inspectAuraSources(swappedRoot, rootCapture.manifest);

    await writeSourceSnapshot(exactCopySnapshot, externalRoot);
    await fs.chmod(externalRoot, 0o700);
    await fs.chmod(rootCapture.filesystem.root.absolutePath, rootCapture.filesystem.root.initialMode);
    await fs.rename(swappedRoot, originalRoot);
    await fs.chmod(originalRoot, rootCapture.filesystem.root.protectedMode);
    await fs.symlink(externalRoot, swappedRoot, process.platform === 'win32' ? 'junction' : 'dir');
    const externalRootBeforeCleanup = await fs.lstat(externalRoot, { bigint: true });

    try {
        await assert.rejects(
            assertSourceCaptureUnchanged(rootCapture, 'Persistent root drift self-test'),
            /became a symlink|identity drifted/,
            'Persistent exact-copy root substitution must fail its lane checkpoint',
        );
        await assert.rejects(
            releaseSourceCapture(rootCapture),
            /became a symlink|identity drifted/,
            'Cleanup must refuse a substituted capture root',
        );
        await prepareCaptureDirectoriesForRemoval(rootCapture);
        const externalRootAfterCleanup = await fs.lstat(externalRoot, { bigint: true });

        assert.equal(
            permissionMode(externalRootAfterCleanup),
            permissionMode(externalRootBeforeCleanup),
            'Rejected cleanup must not chmod a substituted external root',
        );
    } finally {
        if (protectedSourceCaptures.has(rootCapture)) {
            await fs.unlink(swappedRoot);
            await fs.chmod(originalRoot, rootCapture.filesystem.root.initialMode);
            await fs.rename(originalRoot, swappedRoot);
            await reprotectCaptureForSelfTest(rootCapture);
            await releaseSourceCapture(rootCapture);
        }
    }

    console.log('PASS persistent mode, hardlink, and root identity drift fail closed without chmodding substitutes');
}

async function execute(command, args, cwd) {
    return execFileAsync(command, args, {
        cwd,
        maxBuffer: 20 * 1024 * 1024,
        windowsHide: true,
    });
}

async function executeWithSourceCapture(command, args, cwd, capture, label) {
    await assertSourceCaptureUnchanged(capture, `${label} pre-compiler check`);

    try {
        return await execute(command, args, cwd);
    } finally {
        await assertSourceCaptureUnchanged(capture, `${label} post-compiler check`);
    }
}

async function expectFailure(command, args, cwd, pattern, label, sourceCapture = null) {
    try {
        if (sourceCapture === null) {
            await execute(command, args, cwd);
        } else {
            await executeWithSourceCapture(command, args, cwd, sourceCapture, label);
        }
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
    const sourceCapture = await copyAuraSources(path.join(directory, 'aura-source'), 'Tailwind 3 source lane');

    const outputPath = path.join(directory, 'output.css');
    await executeWithSourceCapture(
        process.execPath,
        [tailwindCliPath, '-c', 'tailwind.config.cjs', '-i', 'input.css', '-o', outputPath, '--minify'],
        directory,
        sourceCapture,
        'Tailwind 3 source lane',
    );
    const result = assertCssContract(outputPath, 3);
    enforceOutputBaseline(result, 'tailwind3', 'Tailwind 3 output check');

    console.log(`PASS Tailwind 3.4.19: ${result.assertionCount} parsed assertions (${result.bytes} bytes, sha256 ${result.sha256})`);
}

async function prepareTailwind4() {
    const directory = path.join(temporaryRoot, 'tailwind-4');

    await fs.cp(path.join(fixtureDirectory, 'v4'), directory, { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(directory, 'resources/css/token-contract.css'));
    const sourceCapture = await copyAuraSources(path.join(directory, 'aura-source'), 'Tailwind 4 source lane');

    await execute(process.execPath, [npmCliPath, 'ci', '--ignore-scripts', '--no-audit', '--no-fund'], directory);

    return { directory, sourceCapture };
}

async function buildTailwind4({ directory, sourceCapture }) {
    await executeWithSourceCapture(
        process.execPath,
        [npmCliPath, 'run', 'build'],
        directory,
        sourceCapture,
        'Tailwind 4 source lane',
    );

    const manifest = await readStrictJsonFile(path.join(directory, 'dist/manifest.json'), 'Tailwind 4 Vite manifest');
    const entrypoint = manifest['index.html'];
    const cssFile = entrypoint?.css?.[0]
        ?? (entrypoint?.file?.endsWith('.css') ? entrypoint.file : null);
    assert.ok(cssFile, 'Vite manifest must expose the host CSS entrypoint');

    const outputPath = await resolveGeneratedOutput(path.join(directory, 'dist'), cssFile, 'Vite CSS output');
    const result = assertCssContract(outputPath, 4);
    enforceOutputBaseline(result, 'tailwind4', 'Tailwind 4 output check');

    console.log(`PASS Tailwind 4.3.3 + Vite 8.2.1: ${result.assertionCount} parsed assertions (${result.bytes} bytes, sha256 ${result.sha256})`);
}

async function checkNegativeBoundaries({ directory: tailwind4Directory, sourceCapture: tailwind4SourceCapture }) {
    const v3AgainstV4 = path.join(temporaryRoot, 'v3-against-v4');

    await fs.mkdir(path.join(v3AgainstV4, 'resources/css'), { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'v4/resources/css/app.css'), path.join(v3AgainstV4, 'resources/css/app.css'));
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(v3AgainstV4, 'resources/css/token-contract.css'));
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.config.cjs'), path.join(v3AgainstV4, 'tailwind.config.cjs'));
    const tailwind3SourceCapture = await copyAuraSources(
        path.join(v3AgainstV4, 'aura-source'),
        'Tailwind 3 negative source lane',
    );

    await expectFailure(
        process.execPath,
        [tailwindCliPath, '-c', 'tailwind.config.cjs', '-i', 'resources/css/app.css', '-o', 'output.css'],
        v3AgainstV4,
        /Failed to find ['"]tailwindcss['"]/,
        'Tailwind 3 rejects the v4 CSS entrypoint',
        tailwind3SourceCapture,
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
        tailwind4SourceCapture,
    );
}

let gateFailure = null;

try {
    await assertProductionOutputsPinned('Pre-gate production output check');
    assertProductionOutputDriftIsRejected();
    const sourceSnapshot = await inspectAuraSources(repositoryRoot);
    enforceSourceBaseline(sourceSnapshot, 'Pre-build source check');
    await assertDocumentedSourceBaseline();
    assertDuplicateJsonMembersAreRejected();
    await assertStrictJsonLimitsAreEnforced();
    assertMalformedUnicodeIsRejected();
    await assertCanonicalSourcePathsAreEnforced();
    assertCanonicalRecordFraming();
    assertExactSourceBytesAreAuthenticated();
    await assertClassMetadataDriftIsRejected();
    await assertSourceDriftIsRejected();
    assertOutputContractIsPinned();
    await assertCapturedSourceMutationIsRejected();
    await assertPersistentCaptureDriftIsRejected();
    console.log(`Aura source baseline: ${sourceSnapshot.count} files, sha256 ${sourceBaseline.expectedDigest}`);

    await buildTailwind3();
    const tailwind4Fixture = await prepareTailwind4();
    await buildTailwind4(tailwind4Fixture);
    await checkNegativeBoundaries(tailwind4Fixture);
    await assertProductionOutputsPinned('Post-gate production output check');
} catch (error) {
    gateFailure = error;
} finally {
    const cleanupFailures = [];

    for (const capture of [...protectedSourceCaptures].reverse()) {
        try {
            await releaseSourceCapture(capture);
        } catch (error) {
            cleanupFailures.push(error);

            try {
                await prepareCaptureDirectoriesForRemoval(capture);
            } catch (preparationError) {
                cleanupFailures.push(preparationError);
            }
        }
    }

    if (process.env.AURA_KEEP_FRONTEND_FIXTURE === '1') {
        console.log(`Kept fixture workspace: ${temporaryRoot}`);
    } else {
        try {
            await fs.rm(temporaryRoot, { recursive: true, force: true });
        } catch (error) {
            cleanupFailures.push(error);
        }
    }

    const failures = [gateFailure, ...cleanupFailures].filter(Boolean);

    if (failures.length === 1) {
        throw failures[0];
    }

    if (failures.length > 1) {
        throw new AggregateError(failures, 'Frontend compatibility gate and cleanup both failed');
    }
}
