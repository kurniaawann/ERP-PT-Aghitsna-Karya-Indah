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
        
        // Text Semantic Colors - Untuk tipografi dan konten
        text: {
          primary: '#374151',     // gray-700 - Text utama body
          secondary: '#6B7280',   // gray-500 - Text sekunder/muted
          tertiary: '#9CA3AF',    // gray-400 - Icon default, separator
          heading: '#1F2937',     // gray-800 - Heading dan emphasis
          label: '#4B5563',       // gray-600 - Label form, info field
          placeholder: '#D1D5DB', // gray-300 - Placeholder & disabled
          input: '#111827',       // gray-900 - Input text value
        },
        
        // Background Semantic Colors - Untuk latar belakang elemen
        surface: {
          base: '#FFFFFF',        // white - Background utama
          secondary: '#F9FAFB',   // gray-50 - Filter bg, info box
          hover: '#F3F4F6',       // gray-100 - Hover state, readonly field
          disabled: '#F3F4F6',    // gray-100 - Disabled input
          overlay: '#111827E6',   // gray-900/90 - Modal overlay
        },
        
        // Border Semantic Colors - Untuk garis tepi
        border: {
          light: '#F3F4F6',       // gray-100 - Divider subtle
          DEFAULT: '#E5E7EB',     // gray-200 - Border default, divider
          strong: '#D1D5DB',      // gray-300 - Border input, card border
        },
        
        // Button Variant Colors - State untuk button secondary/cancel
        button: {
          cancel: {
            DEFAULT: '#E5E7EB',   // gray-200 - Cancel button
            hover: '#D1D5DB',     // gray-300 - Cancel button hover
            text: '#1F2937',      // gray-800 - Cancel button text
          },
          inactive: {
            DEFAULT: '#E5E7EB',   // gray-200 - Inactive state
            text: '#4B5563',      // gray-600 - Inactive text
          },
        },
      }, 
    },
  },
  plugins: [],
}

