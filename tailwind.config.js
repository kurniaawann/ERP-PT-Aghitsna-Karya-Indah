/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontSize: {
        xs: ['12px', '16px'],
        sm: ['14px', '20px'],
        base: ['16px', '24px'],
        lg: ['18px', '28px'],
        xl: ['20px', '28px'],
        '2xl': ['24px', '32px'],
        '3xl': ['30px', '36px'],
      },
      colors: {
        primary: {
          DEFAULT: '#2563EB',
          hover: '#1D4ED8',
          light: '#DBEAFE',
        },
        secondary: {
          DEFAULT: '#7C3AED',
          hover: '#6D28D9',
          light: '#EDE9FE',
        },
        success: {
          DEFAULT: '#059669',
          light: '#D1FAE5',
        },
        warning: {
          DEFAULT: '#D97706',
          light: '#FEF3C7',
        },
        error: {
          DEFAULT: '#DC2626',
          light: '#FEE2E2',
        },
        info: {
          DEFAULT: '#2563EB',
          light: '#DBEAFE',
        },
        neutral: '#374151',
        white: '#FFFFFF',
        text: {
          primary: '#374151',
          secondary: '#6B7280',
          tertiary: '#9CA3AF',
          heading: '#111827',
          label: '#4B5563',
          placeholder: '#D1D5DB',
          input: '#111827',
        },
        surface: {
          base: '#FFFFFF',
          secondary: '#F9FAFB',
          hover: '#F3F4F6',
          disabled: '#F3F4F6',
          overlay: '#111827E6',
        },
        border: {
          light: '#F3F4F6',
          DEFAULT: '#E5E7EB',
          strong: '#D1D5DB',
        },
        button: {
          cancel: {
            DEFAULT: '#E5E7EB',
            hover: '#D1D5DB',
            text: '#1F2937',
          },
          inactive: {
            DEFAULT: '#E5E7EB',
            text: '#4B5563',
          },
        },
        btn: {
          search: {
            DEFAULT: '#2563EB',
            hover: '#1D4ED8',
          },
          add: {
            DEFAULT: '#059669',
            hover: '#047857',
          },
          edit: {
            DEFAULT: '#D97706',
            hover: '#B45309',
          },
          delete: {
            DEFAULT: '#DC2626',
            hover: '#B91C1C',
          },
        },
      }, 
    },
  },
  plugins: [],
}

