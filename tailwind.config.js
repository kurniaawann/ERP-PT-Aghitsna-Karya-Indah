/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
     fontSize: {
      sm: ['14px', '20px'],
      base: ['16px', '24px'],
      lg: ['20px', '28px'],
      xl: ['24px', '32px'],
    },
    extend: {
      colors: {
        primary: '#5682B1',
        secondary: '#9333EA',
        white: '#FFFFFF',
        neutral: '#374151',
        info: '#3B82F6',
        success: '#10B981',
        warning: '#FBBF24',
        error: '#EF4444',
      }, 
    },
  },
  plugins: [],
}

