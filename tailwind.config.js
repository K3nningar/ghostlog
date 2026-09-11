import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                destiny: {
                    dark: '#0a0e14',
                    panel: '#141a24',
                    gold: '#d4af37',
                    legendary: '#522f65',
                    exotic: '#ceae33',
                    rare: '#5076a3',
                    uncommon : '#6b8e23',
                    common: '#c3bcb4',
                }   
            }
        }
    },

    plugins: [forms],
};
