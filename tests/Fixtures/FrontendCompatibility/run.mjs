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
const npmCliPath = process.env.npm_execpath;
const tailwindCliPath = path.join(
    repositoryRoot,
    'node_modules',
    'tailwindcss',
    'lib',
    'cli.js',
);
const temporaryRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'aura-frontend-compatibility-'));

assert.ok(npmCliPath, 'Run this fixture through npm run test:frontend-compatibility.');

async function copyFile(source, destination) {
    await fs.mkdir(path.dirname(destination), { recursive: true });
    await fs.copyFile(source, destination);
}

async function copyAuraSources(destination) {
    const manifest = JSON.parse(await fs.readFile(path.join(fixtureDirectory, 'source-files.json'), 'utf8'));
    const digest = crypto.createHash('sha256');

    for (const entry of manifest) {
        const sourcePath = path.resolve(repositoryRoot, entry.path);
        assert.ok(sourcePath.startsWith(`${repositoryRoot}${path.sep}`), `Unsafe source path: ${entry.path}`);

        const source = await fs.readFile(sourcePath, 'utf8');

        for (const className of entry.classes) {
            assert.ok(source.includes(className), `${entry.path} no longer contains ${className}`);
        }

        digest.update(entry.path);
        digest.update('\0');
        digest.update(source);
        await copyFile(sourcePath, path.join(destination, entry.path));
    }

    await copyFile(
        path.join(fixtureDirectory, 'semantic-probe.blade.php'),
        path.join(destination, 'fixtures/semantic-probe.blade.php'),
    );

    return {
        count: manifest.length,
        digest: digest.digest('hex'),
    };
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

async function buildTailwind3(sourceDigest) {
    const directory = path.join(temporaryRoot, 'tailwind-3');

    await fs.mkdir(directory, { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.config.cjs'), path.join(directory, 'tailwind.config.cjs'));
    await copyFile(path.join(fixtureDirectory, 'tailwind-v3.css'), path.join(directory, 'input.css'));
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(directory, 'token-contract.css'));
    const copiedSources = await copyAuraSources(path.join(directory, 'aura-source'));
    assert.equal(copiedSources.digest, sourceDigest, 'Tailwind 3 must scan the shared Aura source snapshot');

    const outputPath = path.join(directory, 'output.css');
    await execute(
        process.execPath,
        [tailwindCliPath, '-c', 'tailwind.config.cjs', '-i', 'input.css', '-o', outputPath, '--minify'],
        directory,
    );
    const result = assertCssContract(outputPath, 3);

    console.log(`PASS Tailwind 3.4.19: ${result.assertionCount} parsed assertions (${result.bytes} bytes)`);
}

async function prepareTailwind4(sourceDigest) {
    const directory = path.join(temporaryRoot, 'tailwind-4');

    await fs.cp(path.join(fixtureDirectory, 'v4'), directory, { recursive: true });
    await copyFile(path.join(fixtureDirectory, 'token-contract.css'), path.join(directory, 'resources/css/token-contract.css'));
    const copiedSources = await copyAuraSources(path.join(directory, 'aura-source'));
    assert.equal(copiedSources.digest, sourceDigest, 'Tailwind 4 must scan the shared Aura source snapshot');

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
    await copyAuraSources(path.join(v3AgainstV4, 'aura-source'));

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
    const sourceSnapshot = await copyAuraSources(path.join(temporaryRoot, 'source-snapshot'));
    console.log(`Aura source snapshot: ${sourceSnapshot.count} files, sha256 ${sourceSnapshot.digest}`);

    await buildTailwind3(sourceSnapshot.digest);
    const tailwind4Directory = await prepareTailwind4(sourceSnapshot.digest);
    await buildTailwind4(tailwind4Directory);
    await checkNegativeBoundaries(tailwind4Directory);
} finally {
    if (process.env.AURA_KEEP_FRONTEND_FIXTURE === '1') {
        console.log(`Kept fixture workspace: ${temporaryRoot}`);
    } else {
        await fs.rm(temporaryRoot, { recursive: true, force: true });
    }
}
