import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
            },
            allUnset: {
                "all-unset": {
                    all: "unset",
                },
            },
        },
    },

    plugins: [forms, typography],
    plugins: [
        function ({ addUtilities }) {
            const newUtilities = {
                ".all-unset": {
                    all: "unset",
                },
            };
            addUtilities(newUtilities);
        },
    ],
};
