
// import * as monaco from 'monaco-editor';

import * as monaco from 'monaco-editor'
// import editorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker'
// import jsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker'
// import cssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker'
// import htmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker'
// import tsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker'


import monokaiTheme from 'monaco-themes/themes/Monokai.json';
import githubLightTheme from 'monaco-themes/themes/GitHub Light.json';
import githubDarkTheme from 'monaco-themes/themes/GitHub Dark.json';

monaco.editor.defineTheme('monokai', monokaiTheme);
monaco.editor.defineTheme('github-light', githubLightTheme);
monaco.editor.defineTheme('github-dark', githubDarkTheme);

window.monaco = monaco;

// window.MonacoEnvironment = {
//   getWorker(_, label) {
//     if (label === 'json') {
//       return new jsonWorker()
//     }
//     if (label === 'css' || label === 'scss' || label === 'less') {
//       return new cssWorker()
//     }
//     if (label === 'html' || label === 'handlebars' || label === 'razor') {
//       return new htmlWorker()
//     }
//     if (label === 'typescript' || label === 'javascript') {
//       return new tsWorker()
//     }
//     return new editorWorker()
//   }
// }

