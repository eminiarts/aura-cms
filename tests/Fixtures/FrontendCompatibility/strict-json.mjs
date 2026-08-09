import { Buffer } from 'node:buffer';
import fs from 'node:fs/promises';
import { TextDecoder } from 'node:util';

const utf8Decoder = new TextDecoder('utf-8', { fatal: true, ignoreBOM: true });

export const STRICT_JSON_MAX_BYTES = 1024 * 1024;
export const STRICT_JSON_MAX_DEPTH = 64;

export function assertWellFormedUnicode(value, label = 'String') {
    if (typeof value !== 'string') {
        throw new TypeError(`${label} must be a string`);
    }

    for (let index = 0; index < value.length; index += 1) {
        const codeUnit = value.charCodeAt(index);

        if (codeUnit >= 0xD800 && codeUnit <= 0xDBFF) {
            const nextCodeUnit = value.charCodeAt(index + 1);

            if (! Number.isInteger(nextCodeUnit) || nextCodeUnit < 0xDC00 || nextCodeUnit > 0xDFFF) {
                throw new SyntaxError(`${label} must contain well-formed Unicode; lone high surrogate at index ${index}`);
            }

            index += 1;
        } else if (codeUnit >= 0xDC00 && codeUnit <= 0xDFFF) {
            throw new SyntaxError(`${label} must contain well-formed Unicode; lone low surrogate at index ${index}`);
        }
    }

    return value;
}

class StrictJsonParser {
    constructor(text, label) {
        this.text = text;
        this.label = label;
        this.position = 0;
    }

    parse() {
        this.skipWhitespace();
        const value = this.parseValue();
        this.skipWhitespace();

        if (this.position !== this.text.length) {
            this.fail('Unexpected trailing content');
        }

        return value;
    }

    parseValue(depth = 0) {
        const character = this.text[this.position];

        if (character === '{') {
            return this.parseObject(depth + 1);
        }

        if (character === '[') {
            return this.parseArray(depth + 1);
        }

        if (character === '"') {
            return this.parseString();
        }

        if (character === 't') {
            return this.parseLiteral('true', true);
        }

        if (character === 'f') {
            return this.parseLiteral('false', false);
        }

        if (character === 'n') {
            return this.parseLiteral('null', null);
        }

        if (character === '-' || this.isDigit(character)) {
            return this.parseNumber();
        }

        this.fail('Expected a JSON value');
    }

    parseObject(depth) {
        this.assertNestingDepth(depth);
        const value = Object.create(null);
        const memberNames = new Set();

        this.position += 1;
        this.skipWhitespace();

        if (this.text[this.position] === '}') {
            this.position += 1;

            return value;
        }

        while (this.position < this.text.length) {
            if (this.text[this.position] !== '"') {
                this.fail('Expected an object member name');
            }

            const memberName = this.parseString();

            if (memberNames.has(memberName)) {
                this.fail(`Duplicate JSON member name ${JSON.stringify(memberName)}`);
            }

            memberNames.add(memberName);
            this.skipWhitespace();
            this.expect(':');
            this.skipWhitespace();
            const memberValue = this.parseValue(depth);
            Object.defineProperty(value, memberName, {
                configurable: true,
                enumerable: true,
                value: memberValue,
                writable: true,
            });
            this.skipWhitespace();

            const character = this.text[this.position];

            if (character === '}') {
                this.position += 1;

                return value;
            }

            this.expect(',');
            this.skipWhitespace();
        }

        this.fail('Unterminated object');
    }

    parseArray(depth) {
        this.assertNestingDepth(depth);
        const value = [];

        this.position += 1;
        this.skipWhitespace();

        if (this.text[this.position] === ']') {
            this.position += 1;

            return value;
        }

        while (this.position < this.text.length) {
            value.push(this.parseValue(depth));
            this.skipWhitespace();

            const character = this.text[this.position];

            if (character === ']') {
                this.position += 1;

                return value;
            }

            this.expect(',');
            this.skipWhitespace();
        }

        this.fail('Unterminated array');
    }

    parseString() {
        const chunks = [];

        this.position += 1;
        let segmentStart = this.position;

        while (this.position < this.text.length) {
            const codeUnit = this.text.charCodeAt(this.position);

            if (codeUnit === 0x22) {
                chunks.push(this.text.slice(segmentStart, this.position));
                this.position += 1;
                const value = chunks.join('');
                assertWellFormedUnicode(value, `${this.label} string`);

                return value;
            }

            if (codeUnit === 0x5C) {
                chunks.push(this.text.slice(segmentStart, this.position));
                this.position += 1;
                chunks.push(this.parseEscape());
                segmentStart = this.position;

                continue;
            }

            if (codeUnit < 0x20) {
                this.fail('Unescaped control character in string');
            }

            this.position += 1;
        }

        this.fail('Unterminated string');
    }

    parseEscape() {
        const character = this.text[this.position];
        const simpleEscapes = {
            '"': '"',
            '\\': '\\',
            '/': '/',
            b: '\b',
            f: '\f',
            n: '\n',
            r: '\r',
            t: '\t',
        };

        if (Object.hasOwn(simpleEscapes, character)) {
            this.position += 1;

            return simpleEscapes[character];
        }

        if (character !== 'u') {
            this.fail('Invalid string escape');
        }

        this.position += 1;
        const start = this.position;
        const end = start + 4;

        if (end > this.text.length) {
            this.fail('Incomplete Unicode escape');
        }

        let codeUnit = 0;

        while (this.position < end) {
            const digit = this.hexValue(this.text.charCodeAt(this.position));

            if (digit === -1) {
                this.fail('Invalid Unicode escape');
            }

            codeUnit = (codeUnit * 16) + digit;
            this.position += 1;
        }

        return String.fromCharCode(codeUnit);
    }

    parseNumber() {
        const start = this.position;

        if (this.text[this.position] === '-') {
            this.position += 1;
        }

        if (this.text[this.position] === '0') {
            this.position += 1;
        } else {
            this.consumeDigits(true);
        }

        if (this.text[this.position] === '.') {
            this.position += 1;
            this.consumeDigits(true);
        }

        if (this.text[this.position] === 'e' || this.text[this.position] === 'E') {
            this.position += 1;

            if (this.text[this.position] === '+' || this.text[this.position] === '-') {
                this.position += 1;
            }

            this.consumeDigits(true);
        }

        return Number(this.text.slice(start, this.position));
    }

    parseLiteral(literal, value) {
        if (! this.text.startsWith(literal, this.position)) {
            this.fail(`Expected ${literal}`);
        }

        this.position += literal.length;

        return value;
    }

    consumeDigits(requireAtLeastOne) {
        const start = this.position;

        while (this.isDigit(this.text[this.position])) {
            this.position += 1;
        }

        if (requireAtLeastOne && this.position === start) {
            this.fail('Expected a digit');
        }
    }

    isDigit(character) {
        return character >= '0' && character <= '9';
    }

    hexValue(codeUnit) {
        if (codeUnit >= 0x30 && codeUnit <= 0x39) {
            return codeUnit - 0x30;
        }

        if (codeUnit >= 0x41 && codeUnit <= 0x46) {
            return codeUnit - 0x41 + 10;
        }

        if (codeUnit >= 0x61 && codeUnit <= 0x66) {
            return codeUnit - 0x61 + 10;
        }

        return -1;
    }

    assertNestingDepth(depth) {
        if (depth > STRICT_JSON_MAX_DEPTH) {
            this.fail(`Maximum nesting depth of ${STRICT_JSON_MAX_DEPTH} exceeded`);
        }
    }

    expect(character) {
        if (this.text[this.position] !== character) {
            this.fail(`Expected ${JSON.stringify(character)}`);
        }

        this.position += 1;
    }

    skipWhitespace() {
        while (
            this.text[this.position] === ' '
            || this.text[this.position] === '\t'
            || this.text[this.position] === '\n'
            || this.text[this.position] === '\r'
        ) {
            this.position += 1;
        }
    }

    fail(message) {
        throw new SyntaxError(`${this.label} at character ${this.position}: ${message}`);
    }
}

export function parseStrictJson(text, label = 'JSON') {
    if (typeof text === 'string' && text.length > STRICT_JSON_MAX_BYTES) {
        throw new SyntaxError(`${label} exceeds maximum byte length of ${STRICT_JSON_MAX_BYTES} bytes`);
    }

    assertWellFormedUnicode(text, `${label} text`);

    if (Buffer.byteLength(text, 'utf8') > STRICT_JSON_MAX_BYTES) {
        throw new SyntaxError(`${label} exceeds maximum byte length of ${STRICT_JSON_MAX_BYTES} bytes`);
    }

    return new StrictJsonParser(text, label).parse();
}

export async function readStrictJsonFile(filePath, label) {
    const handle = await fs.open(filePath, 'r');
    let text;
    let bytes;

    try {
        const stats = await handle.stat();

        if (! stats.isFile()) {
            throw new SyntaxError(`${label} must be a regular file`);
        }

        if (stats.size > STRICT_JSON_MAX_BYTES) {
            throw new SyntaxError(`${label} exceeds maximum byte length of ${STRICT_JSON_MAX_BYTES} bytes`);
        }

        const readBuffer = Buffer.allocUnsafe(STRICT_JSON_MAX_BYTES + 1);
        let byteLength = 0;

        while (byteLength < readBuffer.length) {
            const { bytesRead } = await handle.read(
                readBuffer,
                byteLength,
                readBuffer.length - byteLength,
                null,
            );

            if (bytesRead === 0) {
                break;
            }

            byteLength += bytesRead;
        }

        if (byteLength > STRICT_JSON_MAX_BYTES) {
            throw new SyntaxError(`${label} exceeds maximum byte length of ${STRICT_JSON_MAX_BYTES} bytes`);
        }

        bytes = readBuffer.subarray(0, byteLength);
    } finally {
        await handle.close();
    }

    try {
        text = utf8Decoder.decode(bytes);
    } catch {
        throw new SyntaxError(`${label} must contain valid UTF-8`);
    }

    return parseStrictJson(text, label);
}
