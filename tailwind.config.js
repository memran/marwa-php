const forms = require('@tailwindcss/forms');
const typography = require('@tailwindcss/typography');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/**/*.php',
    './config/**/*.php',
    './modules/**/*.php',
    './modules/**/*.twig',
    './resources/**/*.twig',
    './routes/**/*.php',
    './tests/**/*.php',
  ],
  darkMode: ['class', '[data-admin-theme="dark"]'],
  theme: {
    extend: {
      colors: {
        app: {
          bg: 'rgb(var(--app-bg) / <alpha-value>)',
          surface: 'rgb(var(--app-surface) / <alpha-value>)',
          surface2: 'rgb(var(--app-surface-2) / <alpha-value>)',
          border: 'rgb(var(--app-border) / <alpha-value>)',
          text: 'rgb(var(--app-text) / <alpha-value>)',
          muted: 'rgb(var(--app-muted) / <alpha-value>)',
          accent: 'rgb(var(--app-accent) / <alpha-value>)',
          accentSoft: 'rgb(var(--app-accent-soft) / <alpha-value>)',
          success: 'rgb(var(--app-success) / <alpha-value>)',
          warning: 'rgb(var(--app-warning) / <alpha-value>)',
          danger: 'rgb(var(--app-danger) / <alpha-value>)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'sans-serif'],
        display: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'sans-serif'],
      },
      boxShadow: {
        glow: '0 30px 80px rgba(2, 6, 23, 0.42)',
      },
      backgroundImage: {
        'radial-hero':
          'radial-gradient(circle at top, rgba(59, 130, 246, 0.28), transparent 40%), radial-gradient(circle at 85% 18%, rgba(34, 211, 238, 0.18), transparent 24%), radial-gradient(circle at 12% 84%, rgba(16, 185, 129, 0.12), transparent 24%)',
      },
    },
  },
  plugins: [forms({ strategy: 'class' }), typography],
};
