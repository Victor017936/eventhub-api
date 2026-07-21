import js from '@eslint/js';
import globals from 'globals';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

export default [
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/build/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },

    js.configs.recommended,

    ...tseslint.configs.recommended,

    ...pluginVue.configs['flat/essential'],

    {
        files: [
            'resources/js/**/*.{js,ts,vue}',
        ],

        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.es2021,
            },

            parserOptions: {
                parser: tseslint.parser,
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
        },

        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },

    {
        files: [
            'vite.config.js',
            'eslint.config.js',
        ],

        languageOptions: {
            globals: {
                ...globals.node,
            },
        },
    },
];
