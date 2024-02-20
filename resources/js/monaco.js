// import ApexCharts from 'apexcharts'

// window.ApexCharts = ApexCharts;

import 'monaco-editor/esm/vs/editor/editor.api';

window.MonacoEnvironment = {
    getWorkerUrl: (moduleId, label) => {
        if (label === 'html' || label === 'handlebars') {
            return '/html.worker.js';
        }

        return '/editor.worker.js';
    },
    getWorker: (moduleId, label) => {
        return new Worker(window.MonacoEnvironment.getWorkerUrl(moduleId, label));
    },
};
