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
assert.equal(sourceBaseline.manifest, path.basename(sourceManifestPath), 'Source baseline must name the selected-source manifest');
assert.equal(sourceBaseline.algorithm, 'sha256', 'Source baseline must use SHA-256');
assert.equal(sourceBaseline.lineEndings, 'lf', 'Source baseline must normalize line endings to LF');
assert.match(sourceBaseline.expectedDigest, /^[a-f0-9]{64}$/, 'Source baseline must contain a SHA-256 digest');
assert.ok(Array.isArray(sourceManifest) && sourceManifest.length > 0, 'Source manifest must select Aura source files');

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

async function inspectAuraSources(sourceRoot) {
    const digest = crypto.createHash('sha256');
    const files = [];

    for (const entry of sourceManifest) {
        const sourcePath = resolveSelectedSource(sourceRoot, entry.path);
        const source = await fs.readFile(sourcePath, 'utf8');

        for (const className of entry.classes) {
            assert.ok(source.includes(className), `${entry.path} no longer contains ${className}`);
        }

        digest.update(entry.path);
        digest.update('\0');
        digest.update(source.replace(/\r\n/g, '\n'));
        files.push({ path: entry.path, source });
    }

    return {
        count: sourceManifest.length,
        digest: digest.digest('hex'),
        files,
    };
}

function enforceSourceBaseline(snapshot, label) {
    assert.equal(
        snapshot.digest,
        sourceBaseline.expectedDigest,
        `${label}: selected Aura source drifted. Review the change, then update source-baseline.json and the documented digest intentionally.`,
    );
}

async function writeSourceSnapshot(snapshot, destination) {
    for (const file of snapshot.files) {
        const outputPath = path.join(destination, file.path);

        await fs.mkdir(path.dirname(outputPath), { recursive: true });
        await fs.writeFile(outputPath, file.source, 'utf8');
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
        /["']\.\/base["'] is not exported under the conditions? .*?["']style["']/s,
        'Tailwind 4 Vite rejects Aura\'s v3 CSS entrypoint',
    );
}

try {
    const sourceSnapshot = await inspectAuraSources(repositoryRoot);
    enforceSourceBaseline(sourceSnapshot, 'Pre-build source check');
    await assertDocumentedSourceBaseline();
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
