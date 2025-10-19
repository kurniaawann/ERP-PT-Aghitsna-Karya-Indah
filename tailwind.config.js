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
        primary: {
          DEFAULT: '#3B82F6', // Blue-500
          hover: '#2563EB',   // Blue-600
          light: '#DBEAFE',   // Blue-100
        },
        secondary: {
          DEFAULT: '#8B5CF6', // Violet-500
          hover: '#7C3AED',   // Violet-600
          light: '#EDE9FE',   // Violet-100
        },
        // Button Colors - Reusable
        btn: {
          search: {
            DEFAULT: '#3B82F6', // Blue-500 (Cari)
            hover: '#2563EB',   // Blue-600
          },
          add: {
            DEFAULT: '#10B981', // Emerald-500 (Tambah)
            hover: '#059669',   // Emerald-600
          },
          edit: {
            DEFAULT: '#F59E0B', // Amber-500 (Edit)
            hover: '#D97706',   // Amber-600
          },
          delete: {
            DEFAULT: '#EF4444', // Red-500 (Hapus)
            hover: '#DC2626',   // Red-600
          },
        },
        // Status Colors untuk Toast
        success: {
          DEFAULT: '#10B981', // Emerald-500
          light: '#D1FAE5',   // Emerald-100
        },
        warning: {
          DEFAULT: '#F59E0B', // Amber-500
          light: '#FEF3C7',   // Amber-100
        },
        error: {
          DEFAULT: '#EF4444', // Red-500
          light: '#FEE2E2',   // Red-100
        },
        info: {
          DEFAULT: '#3B82F6', // Blue-500
          light: '#DBEAFE',   // Blue-100
        },
        // Neutral Colors
        neutral: '#374151',
        white: '#FFFFFF',
      }, 
    },
  },
  plugins: [],
}

