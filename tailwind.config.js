/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#edfdf6',
          100: '#d3f9e9',
          200: '#aaf1d5',
          300: '#72e4ba',
          400: '#38ce9b',
          500: '#16b382',
          600: '#0c9069',
          700: '#0b7356',
          800: '#0c5b45',
          900: '#0b4b39',
          950: '#052a20',
        },
      },
      fontFamily: {
        sans: ['var(--font-sans)', 'sans-serif'],
        mono: ['var(--font-mono)', 'monospace'],
      },
    },
  },
  plugins: [],
}
