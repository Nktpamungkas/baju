/**
 * NALE — design tokens. Copy the `theme.extend` block into your Laravel
 * project's tailwind.config.js. These values ARE the design system; keep them
 * here and only reference them by name (text-ink, bg-canvas, rounded-pill, …)
 * so the look never drifts as you add pages.
 */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        canvas:     '#FBFAF8', // page background
        ink:        '#1C1A17', // primary text / dark buttons
        muted:      '#6E685E', // secondary text
        faint:      '#A39C90', // tertiary / meta text
        line:       '#ECE7DF', // hairline borders
        panel:      '#F6F2EB', // warm panel / footer background
        cardbg:     '#EFEAE2', // image placeholder background
        accent:     '#BA6A45', // clay accent (eyebrows, highlights)
        accentsoft: '#D9A88C', // soft accent (dark sections)
      },
      fontFamily: {
        // display: serif for headings + prices; sans: UI text
        display: ['Newsreader', 'serif'],
        sans:    ['"Hanken Grotesk"', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        card: '6px',
        lg2:  '8px',
        pill: '100px',
      },
      maxWidth: {
        content: '1180px', // standard page container
      },
      letterSpacing: {
        eyebrow: '0.26em',
      },
    },
  },
  plugins: [],
};
