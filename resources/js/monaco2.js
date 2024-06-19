
// import * as monaco from 'monaco-editor';

// resources/js/monaco.js
import * as monaco from 'monaco-editor/esm/vs/editor/editor.api';

import 'monaco-editor/esm/vs/basic-languages/php/php.contribution';
import 'monaco-editor/esm/vs/basic-languages/javascript/javascript.contribution';
import 'monaco-editor/esm/vs/basic-languages/html/html.contribution';
import 'monaco-editor/esm/vs/basic-languages/css/css.contribution';
import 'monaco-editor/esm/vs/basic-languages/markdown/markdown.contribution';
import 'monaco-editor/esm/vs/basic-languages/mdx/mdx.contribution';
import 'monaco-editor/esm/vs/basic-languages/mysql/mysql.contribution';
import 'monaco-editor/esm/vs/basic-languages/twig/twig.contribution';
import 'monaco-editor/esm/vs/basic-languages/typescript/typescript.contribution';

import 'monaco-editor/esm/vs/language/json/monaco.contribution';

// import monacoJsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker';
import monacoCssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker';
import monacoHtmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker';
import monacoTsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker';
import monacoEditorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker';

import monokaiTheme from 'monaco-themes/themes/Monokai.json';
import githubLightTheme from 'monaco-themes/themes/GitHub Light.json';
import githubDarkTheme from 'monaco-themes/themes/GitHub Dark.json';



window.monaco = monaco;

window.MonacoEnvironment = {
  getWorker: function (moduleId, label) {
    switch (label) {
      // case 'json':
      //   return new monacoJsonWorker();
      case 'css':
      case 'scss':
      case 'less':
        return new monacoCssWorker();
      case 'html':
      case 'handlebars':
      case 'razor':
        return new monacoHtmlWorker();
      case 'typescript':
      case 'javascript':
        return new monacoTsWorker();
      default:
        return new monacoEditorWorker();
    }
  }
};

monaco.editor.defineTheme('monokai', monokaiTheme);
monaco.editor.defineTheme('github-light', githubLightTheme);
monaco.editor.defineTheme('github-dark', githubDarkTheme);
