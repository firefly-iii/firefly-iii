/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.twig", // Add twig files
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}