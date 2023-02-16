const colors = require('tailwindcss/colors')

module.exports = {
  content: [
    './src/**/*.{html,js}',
    '../../../**/*.twig',
    './templates/**/*.twig',
    './safelist.txt'
  ],
  theme: {
    fontFamily: {
      'header': ['"Montserrat"', '"sans-serif"'],
      'body': ['"Source Sans Pro"','"Helvetica Neue"' ,'Helvetica','Arial' ,'sans-serif']
     },
     colors: {
      'oxford-blue': '#002554',
      'sap-green': '#53782b',
      'blue-sapphire': '#165c7d',
      'tea-green': '#c4dea6',
      'sizzling-red': '#f0515a',
      'cyber-yellow': '#ffcd00',
      'black': '#171717',
      'darkestgray': '#262626',
      'darkergray': '#525252',
      'darkgray': '#737373',
      'gray': '#a3a3a3',
      'lightgray': '#d4d4d4',
      'lightergray': '#e5e5e5',
      'lightestgray': '#f5f5f5',      
      'white': '#ffffff',
      'link': '#16567d',
      'body': '#404040',
      'headings': '#5c5d5d'      
    },
    extend: {
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--gradient-color-stops))'
      },
      typography: ({theme}) => ({
      neutral: {
        css:{
          'max-width': 'none',
          '--tw-prose-body': '#404040',
          '--tw-prose-headings': '#5c5d5d',
          '--tw-prose-lead': '#525252',
          '--tw-prose-links': '#16567d',
          '--tw-prose-bold': '#171717',
          '--tw-prose-counters': '#737373',
          '--tw-prose-bullets': '#d4d4d4',
          '--tw-prose-hr': '#e5e5e5',
          '--tw-prose-quotes': '#171717',
          '--tw-prose-quote-borders': '#e5e5e5',
          '--tw-prose-captions': '#737373',
          '--tw-prose-code': '#171717',
          '--tw-prose-pre-code': '#e5e5e5',
          '--tw-prose-pre-bg': '#262626',
          '--tw-prose-th-borders': '#d4d4d4',
          '--tw-prose-td-borders': '#e5e5e5',
          '--tw-prose-invert-body': '#d4d4d4',
          '--tw-prose-invert-headings': '#fff',
          '--tw-prose-invert-lead': '#a3a3a3',
          '--tw-prose-invert-links': '#fff',
          '--tw-prose-invert-bold': '#fff',
          '--tw-prose-invert-counters': '#a3a3a3',
          '--tw-prose-invert-bullets': '#525252',
          '--tw-prose-invert-hr': '#404040',
          '--tw-prose-invert-quotes': '#f5f5f5',
          '--tw-prose-invert-quote-borders': '#404040',
          '--tw-prose-invert-captions': '#a3a3a3',
          '--tw-prose-invert-code': '#fff',
          '--tw-prose-invert-pre-code': '#d4d4d4',
          '--tw-prose-invert-pre-bg': 'rgb(0 0 0 / 50%)',
          '--tw-prose-invert-th-borders': '#525252',
          '--tw-prose-invert-td-borders': '#404040'
      }}
      })
    }
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('tailwind-safelist-generator')({
      patterns: [
        'text-{colors}',
        'text-{fontSize}',
        'text-transparent',
        'text-right',
        'text-left',
        'italic',
        'border-{colors}',
        'border-{borderWidth}',
        'border-t-{borderWidth}',
        'border-r-{borderWidth}',
        'border-t-[5px]',
        'border-[1px]',
        'border-t-[1px]',
        'border-r-[1px]',
        'border-b-[1px]',
        'border-l-[1px]',
        'ring-0',
        'shadow-transparent',
        'outline-0',
        'md:border-r-[1px]',
        'lg:border-r-[1px]',
        'shadow-red-300',
        'pt-{spacing}',
        'pr-{spacing}',
        'pb-{spacing}',
        'pl-{spacing}',
        '-ml-{spacing}',
        'mt-{spacing}',
        'mr-{spacing}',
        'mx-{spacing}',
        'my-{spacing}',
        '{screens}:mt-{spacing}',
        '{screens}:mr-{spacing}',
        '{screens}:mb-{spacing}',
        '{screens}:ml-{spacing}',                        
        'bg-{colors}',
        'bg-gray-100',
        'gap-{gap}',
        '{screens}:gap-{gap}',
        'gap-y-{gap}',
        'gap-x-{gap}',
        '{screens}:grid-cols-{gridColumn}',
        'sm:grid-cols-1',
        'md:grid-cols-2',
        'md:grid-cols-6',
        'grid-cols-{columns}',
        'col-span-{columns}',
        'row-span-{columns}',
        '{screens}:col-span-{columns}',
        '{screens}:col-start-{gridColumnStart}',
        '{screens}:order-{order}',
        '{screens}:grid-rows-{gridRow}',
        '{screens}:row-span-{columns}',
        '{screens}:row-start-{gridRowStart}', 
        '{screens}:justify-start',
        '{screens}:justify-end',
        '2xl:mb-8', 
        'sm:mt-0',
        'md:mt-0',
        'lg:mt-0',
        'lg:pl-3',
        'sm:block',
        'flex-nowrap',
        'flex-wrap',
        'flex-row-reverse',
        'max-w-[350px]',
        'md:grid-cols-[.62fr_.38fr]',
        'md:grid-cols-[.38fr_.62fr]',
        'grid-cols-[1fr]', 
        'w-24', 
        'h-0',
        'sm:h-auto',      
        'overflow-hidden',
        'aspect-video'
      ],
    }),
  ],
  
  prefix: ''
}