/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./core/**/*.php",
    "./includes/**/*.php",
    "./admin/**/*.php",
    "./api/**/*.php",
    "./src/**/*.{js,jsx}",
  ],
  theme: {
    extend: {
      /**
       * ========================================
       * 汇森科技 Design System v2.0
       * B2B 专业批发风格
       * ========================================
       */

      // 品牌色彩系统
      colors: {
        // 主品牌红 - 沉稳专业
        primary: {
          DEFAULT: '#E63935',
          50:  '#FEF2F2',
          100: '#FEE2E2',
          200: '#FECACA',
          300: '#FCA5A5',
          400: '#F87171',
          500: '#E63935',  // 主色
          600: '#CC2F2B',
          700: '#B91C1C',
          800: '#991B1B',
          900: '#7F1D1D',
          950: '#450A0A',
        },

        // 深蓝灰 - 导航/侧边栏
        secondary: {
          DEFAULT: '#1E293B',
          50:  '#F8FAFC',
          100: '#F1F5F9',
          200: '#E2E8F0',
          300: '#CBD5E1',
          400: '#94A3B8',
          500: '#64748B',
          600: '#475569',
          700: '#334155',
          800: '#1E293B',  // 主色
          900: '#0F172A',
          950: '#020617',
        },

        // 功能色
        background: '#F4F6F8',  // 高级灰底色
        surface: '#FFFFFF',     // 卡片背景

        // 语义色
        success: {
          DEFAULT: '#22C55E',
          light: '#DCFCE7',
        },
        warning: {
          DEFAULT: '#F59E0B',
          light: '#FEF3C7',
        },
        error: {
          DEFAULT: '#EF4444',
          light: '#FEE2E2',
        },
        info: {
          DEFAULT: '#3B82F6',
          light: '#DBEAFE',
        },
      },

      // 字体系统
      fontFamily: {
        sans: [
          'Inter',
          'system-ui',
          '-apple-system',
          'BlinkMacSystemFont',
          '"Segoe UI"',
          'Roboto',
          '"Helvetica Neue"',
          'Arial',
          '"Noto Sans"',
          'sans-serif',
          '"Apple Color Emoji"',
          '"Segoe UI Emoji"',
          '"Segoe UI Symbol"',
          '"Noto Color Emoji"',
        ],
        // 数字/价格专用字体
        mono: [
          'JetBrains Mono',
          'SF Mono',
          'Monaco',
          'Inconsolata',
          'Fira Code',
          'monospace',
        ],
      },

      // 字体大小
      fontSize: {
        'xs':   ['0.75rem', { lineHeight: '1rem' }],
        'sm':   ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg':   ['1.125rem', { lineHeight: '1.75rem' }],
        'xl':   ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl':  ['1.5rem', { lineHeight: '2rem' }],
        '3xl':  ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl':  ['2.25rem', { lineHeight: '2.5rem' }],
        // 价格专用
        'price-sm': ['1.125rem', { lineHeight: '1.5rem', fontWeight: '600' }],
        'price-md': ['1.5rem', { lineHeight: '2rem', fontWeight: '700' }],
        'price-lg': ['2rem', { lineHeight: '2.5rem', fontWeight: '700' }],
      },

      // 圆角系统 - 以 sm(4px) 为主，md(8px) 为辅
      borderRadius: {
        'none': '0',
        'sm':   '4px',   // 主要圆角
        'DEFAULT': '6px',
        'md':   '8px',   // 辅助圆角
        'lg':   '12px',
        'xl':   '16px',
        '2xl':  '20px',
        'full': '9999px',
      },

      // 阴影系统
      boxShadow: {
        'sm':    '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'DEFAULT': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
        'md':    '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
        'lg':    '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
        'xl':    '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
        // 卡片悬浮效果
        'card':       '0 2px 8px rgba(0, 0, 0, 0.06)',
        'card-hover': '0 8px 24px rgba(0, 0, 0, 0.12)',
        // 顶部导航
        'nav':        '0 1px 3px rgba(0, 0, 0, 0.08)',
      },

      // 间距扩展
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },

      // 容器配置
      container: {
        center: true,
        padding: {
          DEFAULT: '1rem',
          sm: '1.5rem',
          lg: '2rem',
        },
      },

      // 最大宽度
      maxWidth: {
        'container': '1280px',
        'content':   '1024px',
      },

      // 过渡动画
      transitionDuration: {
        '150': '150ms',
        '200': '200ms',
        '250': '250ms',
      },

      // Z-index 层级规范
      zIndex: {
        'dropdown': '100',
        'sticky':   '200',
        'modal':    '300',
        'popover':  '400',
        'tooltip':  '500',
      },

      // 动画
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'pulse-soft': 'pulseSoft 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        pulseSoft: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.7' },
        },
      },
    },
  },
  plugins: [],
}
