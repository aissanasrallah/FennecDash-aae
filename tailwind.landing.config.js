/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './application/views/landing/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
      },
      colors: {
        fennec: {
          bg: '#F7F7F9',
          card: '#FFFFFF',
          text: '#1F1F1F',
          muted: '#6B7280',
          border: '#E5E7EB',
          purple: '#6B21A8',
          'purple-hover': '#581C87',
          'purple-light': '#A855F7',
          'purple-lighter': '#C084FC',
          gold: '#F9BC3B',
          'gold-dark': '#D97706',
        }
      },
      borderRadius: {
        lg: '8px',
      }
    }
  },
  plugins: [],
};
