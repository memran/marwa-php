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
  theme: {
    extend: {
      boxShadow: {
        glow: '0 30px 80px rgba(2, 6, 23, 0.42)',
      },
      backgroundImage: {
        'radial-hero':
          'radial-gradient(circle at top, rgba(59, 130, 246, 0.28), transparent 40%), radial-gradient(circle at 85% 18%, rgba(34, 211, 238, 0.18), transparent 24%), radial-gradient(circle at 12% 84%, rgba(16, 185, 129, 0.12), transparent 24%)',
      },
    },
  },
  plugins: [],
};
