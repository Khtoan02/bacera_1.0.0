/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./app/**/*.php",
    "./assets/js/**/*.js",
    "./templates/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#4f46e5', // Example custom color
        secondary: '#10b981',
      }
    },
  },
  plugins: [],
}
