/** Décor des diapositives : formes plates, légères à charger, sans photo. */
export default function SlideArt({ variant }) {
  return (
    <svg
      aria-hidden="true"
      viewBox="0 0 400 400"
      className="pointer-events-none absolute -bottom-16 -right-16 size-72 md:bottom-auto md:right-6 md:top-1/2 md:size-[30rem] md:-translate-y-1/2"
    >
      {variant === 'bubbles' && (
        <>
          <circle cx="230" cy="200" r="150" className="fill-white/10" />
          <circle cx="300" cy="110" r="64" className="fill-citron" />
          <circle cx="170" cy="300" r="42" className="fill-accent" />
          <circle cx="330" cy="290" r="26" className="fill-white/30" />
          <circle cx="110" cy="140" r="16" className="fill-white/30" />
        </>
      )}

      {variant === 'rings' && (
        <>
          <circle cx="220" cy="200" r="170" className="fill-none stroke-night/15" strokeWidth="2" />
          <circle cx="220" cy="200" r="130" className="fill-none stroke-night/20" strokeWidth="2" />
          <circle cx="220" cy="200" r="90" className="fill-night" />
          <text x="220" y="212" textAnchor="middle" className="fill-citron font-display text-4xl font-semibold">
            24–48 h
          </text>
        </>
      )}

      {variant === 'leaves' && (
        <>
          <ellipse cx="240" cy="190" rx="46" ry="130" transform="rotate(35 240 190)" className="fill-primary" />
          <ellipse cx="170" cy="230" rx="34" ry="100" transform="rotate(-30 170 230)" className="fill-night/80" />
          <ellipse cx="300" cy="270" rx="28" ry="80" transform="rotate(70 300 270)" className="fill-white/50" />
          <circle cx="110" cy="110" r="40" className="fill-citron" />
        </>
      )}
    </svg>
  )
}