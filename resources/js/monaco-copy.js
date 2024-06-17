import 'monaco-editor/esm/vs/editor/editor.all.js';

import * as monaco from 'monaco-editor/esm/vs/editor/editor.api';

import 'monaco-editor/esm/vs/basic-languages/php/php.contribution';
import 'monaco-editor/esm/vs/basic-languages/javascript/javascript.contribution';

import 'monaco-editor/esm/vs/basic-languages/html/html.contribution';
import 'monaco-editor/esm/vs/basic-languages/css/css.contribution';

// import 'monaco-editor/esm/vs/basic-languages/json/json.contribution';

import 'monaco-editor/esm/vs/basic-languages/markdown/markdown.contribution';
import 'monaco-editor/esm/vs/basic-languages/mdx/mdx.contribution';
import 'monaco-editor/esm/vs/basic-languages/mysql/mysql.contribution';

import 'monaco-editor/esm/vs/basic-languages/twig/twig.contribution';
import 'monaco-editor/esm/vs/basic-languages/typescript/typescript.contribution';

// https://vitejs.dev/guide/features.html#web-workers
import monacoJsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker'
import monacoCssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker'
import monacoHtmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker'
import monacoTsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker'
import monacoEditorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker'

window.monaco = monaco;

const isDev = false;

window.MonacoEnvironment = {
  getWorker: function (workerId, label) {
    console.debug(`* lazy imported Monaco Editor worker id '${workerId}', label '${label}'`)
    const getWorkerModule = (moduleUrl, label) => {
      return new Worker(new URL('/node_modules/monaco-editor/esm/vs/' + moduleUrl + '.js?worker', import.meta.url), {
        name: label,
        type: 'module'
      })
    }
    switch (label) {
      case 'json':
        return isDev ? getWorkerModule('language/json/json.worker', label) : new monacoJsonWorker()
      case 'css':
      case 'scss':
      case 'less':
        return isDev ? getWorkerModule('language/css/css.worker', label) : new monacoCssWorker()
      case 'html':
      case 'handlebars':
      case 'razor':
        return isDev ? getWorkerModule('language/html/html.worker', label) : new monacoHtmlWorker()
      case 'typescript':
      case 'javascript':
        return isDev ? getWorkerModule('language/typescript/ts.worker', label) : new monacoTsWorker()
      default:
        return isDev ? getWorkerModule('editor/editor.worker', label) : new monacoEditorWorker()
    }
  }
}
