/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./includes/**/*.php",
    "./admin/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Anton"', '"Arial Narrow"', 'sans-serif'],
        sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      // Le site utilise des valeurs d'opacité fines (text-ink/65, bg-ink/98...)
      // qui ne font pas partie de l'échelle par défaut de Tailwind
      // (0,5,10,20,25,30,40,50,60,70,75,80,90,95,100). Sans cette extension,
      // les classes correspondantes ne sont tout simplement pas générées.
      opacity: {
        15: '0.15',
        45: '0.45',
        55: '0.55',
        65: '0.65',
        85: '0.85',
        98: '0.98',
      },
      colors: {
        brand: {
          50: 'rgb(var(--brand-50) / <alpha-value>)',
          100: 'rgb(var(--brand-100) / <alpha-value>)',
          200: 'rgb(var(--brand-200) / <alpha-value>)',
          300: 'rgb(var(--brand-300) / <alpha-value>)',
          400: 'rgb(var(--brand-400) / <alpha-value>)',
          500: 'rgb(var(--brand-500) / <alpha-value>)',
          600: 'rgb(var(--brand-600) / <alpha-value>)',
          700: 'rgb(var(--brand-700) / <alpha-value>)',
          900: 'rgb(var(--brand-900) / <alpha-value>)',
        },
        ink: {
          DEFAULT: 'rgb(var(--ink) / <alpha-value>)',
          dark: 'rgb(var(--ink-dark) / <alpha-value>)',
          soft: 'rgb(var(--ink-soft) / <alpha-value>)',
        },
        rating: 'rgb(var(--rating) / <alpha-value>)',
        success: {
          50: 'rgb(var(--success-50) / <alpha-value>)',
          100: 'rgb(var(--success-100) / <alpha-value>)',
          400: 'rgb(var(--success-400) / <alpha-value>)',
          500: 'rgb(var(--success-500) / <alpha-value>)',
          600: 'rgb(var(--success-600) / <alpha-value>)',
          700: 'rgb(var(--success-700) / <alpha-value>)',
        },
      },
      boxShadow: {
        glow: '0 0 60px -10px rgba(234,88,12,.45)',
        card: '0 18px 40px -20px rgba(17,17,17,.25)',
      },
      keyframes: {
        floaty: {
          '0%,100%': { transform: 'translateY(0px) rotate(0deg)' },
          '50%': { transform: 'translateY(-14px) rotate(3deg)' },
        },
        sizzle: {
          '0%,100%': { transform: 'scaleY(1) translateY(0)', opacity: .55 },
          '50%': { transform: 'scaleY(1.15) translateY(-6px)', opacity: .9 },
        },
        marquee: {
          '0%': { transform: 'translateX(0)' },
          '100%': { transform: 'translateX(-50%)' },
        },
        popIn: {
          '0%': { transform: 'scale(.85)', opacity: 0 },
          '100%': { transform: 'scale(1)', opacity: 1 },
        },
      },
      animation: {
        floaty: 'floaty 5s ease-in-out infinite',
        sizzle: 'sizzle 2.4s ease-in-out infinite',
        marquee: 'marquee 26s linear infinite',
        popIn: 'popIn .4s cubic-bezier(.34,1.56,.64,1) both',
      },
    },
  },
  plugins: [],
};