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
        primary: {
          900: '#3d2f26',
          800: '#4d3d32',
          700: '#6b5344',
          600: '#8d6a54',
          500: '#a9846b',
          400: '#c0a28e',
          300: '#d3bfb1',
          200: '#ded5cd',
          100: '#e9e1d9',
          DEFAULT: '#8d6a54',
        },
        neutral: {
          300: '#eae3d1',
          200: '#f1eee1',
          100: '#f8f7f3',
          DEFAULT: '#eae3d1',
        },
        accent: {
          600: '#c8513b',
          500: '#d95f47',
          400: '#e67258',
          DEFAULT: '#d95f47',
        }
      },
      fontFamily: {
        sans: ['"Bricolage Grotesque"', 'sans-serif'],
        display: ['"Bricolage Grotesque"', 'sans-serif'],
        serif: ['"Gowun Batang"', 'serif'],
      },
      fontSize: {
        'h1': ['28px', { lineHeight: '29px', letterSpacing: '-0.03em', fontWeight: '400' }],
        'h2': ['22px', { lineHeight: '24px', letterSpacing: '-0.03em', fontWeight: '400' }],
        'h3': ['18px', { lineHeight: '21px', letterSpacing: '-0.03em', fontWeight: '400' }],
        'body-reg': ['18px', { lineHeight: '22px', letterSpacing: '0em', fontWeight: '400' }],
        'body-small': ['15px', { lineHeight: '14px', letterSpacing: '-0.02em', fontWeight: '400' }],
        'nav-link': ['15px', { lineHeight: 'normal', letterSpacing: '0em', fontWeight: '400' }],
      }
    },
  },
  plugins: [],
}
