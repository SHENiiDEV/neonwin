/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
    "./resources/**/*.vue",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        cyber: {
          bg: '#0B0E14',
          card: '#121622',
          cardHover: '#181F30',
          sidebar: '#0E121B',
          header: '#0E121C',
          border: '#1E2638',
          borderGlow: '#8B5CF6',
          muted: '#8E9BB0',
        },
        neon: {
          purple: '#8B5CF6',
          purpleLight: '#A855F7',
          pink: '#EC4899',
          blue: '#3B82F6',
          cyan: '#06B6D4',
          gold: '#F59E0B',
          goldLight: '#FBBF24',
          green: '#10B981',
          greenLight: '#22C55E',
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        heading: ['Montserrat', 'sans-serif'],
        display: ['Orbitron', 'sans-serif'],
      },
      boxShadow: {
        'neon-purple': '0 0 20px -3px rgba(168, 85, 247, 0.45)',
        'neon-purple-lg': '0 0 35px -5px rgba(168, 85, 247, 0.65)',
        'neon-blue': '0 0 20px -3px rgba(59, 130, 246, 0.45)',
        'neon-cyan': '0 0 20px -3px rgba(6, 182, 212, 0.45)',
        'neon-gold': '0 0 20px -3px rgba(245, 158, 11, 0.45)',
        'neon-green': '0 0 20px -3px rgba(16, 185, 129, 0.45)',
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'cyber-gradient': 'linear-gradient(135deg, #8B5CF6 0%, #D946EF 50%, #3B82F6 100%)',
        'gold-gradient': 'linear-gradient(135deg, #F59E0B 0%, #FBBF24 50%, #D97706 100%)',
      }
    },
  },
  plugins: [],
}
