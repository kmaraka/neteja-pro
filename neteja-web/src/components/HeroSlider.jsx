import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router'
import { ChevronLeft, ChevronRight, Pause, Play } from './icons'
import SlideArt from './SlideArt'

const INTERVAL_MS = 6000

function prefersReducedMotion() {
  return (
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  )
}

export default function HeroSlider({ slides }) {
  const count = slides.length
  const [index, setIndex] = useState(0)
  // Le défilement automatique est désactivé d'office si le système demande de réduire les animations.
  const [playing, setPlaying] = useState(() => !prefersReducedMotion())
  const [engaged, setEngaged] = useState(false) // souris ou clavier sur le bandeau : on suspend
  const touchStartX = useRef(null)

  const go = useCallback((target) => setIndex(((target % count) + count) % count), [count])

  useEffect(() => {
    if (!playing || engaged || count < 2) return undefined

    const timer = setInterval(() => setIndex((current) => (current + 1) % count), INTERVAL_MS)

    return () => clearInterval(timer)
  }, [playing, engaged, count])

  function onTouchEnd(event) {
    if (touchStartX.current === null) return

    const delta = event.changedTouches[0].clientX - touchStartX.current
    touchStartX.current = null

    if (Math.abs(delta) > 50) go(index + (delta < 0 ? 1 : -1)) // balayage tactile
  }

  function onKeyDown(event) {
    if (event.key === 'ArrowLeft') go(index - 1)
    if (event.key === 'ArrowRight') go(index + 1)
  }

  return (
    <section
      aria-roledescription="carousel"
      aria-label="À la une"
      className="relative overflow-hidden"
      onMouseEnter={() => setEngaged(true)}
      onMouseLeave={() => setEngaged(false)}
      onFocus={() => setEngaged(true)}
      onBlur={() => setEngaged(false)}
      onKeyDown={onKeyDown}
      onTouchStart={(event) => {
        touchStartX.current = event.touches[0].clientX
      }}
      onTouchEnd={onTouchEnd}
    >
      <div className="relative min-h-[26rem] md:min-h-[30rem]" aria-live={playing && !engaged ? 'off' : 'polite'}>
        {slides.map((slide, position) => {
          const active = position === index

          return (
            <div
              key={slide.id}
              role="group"
              aria-roledescription="slide"
              aria-label={`${position + 1} sur ${count}`}
              aria-hidden={!active}
              inert={!active}
              className={`absolute inset-0 transition-opacity duration-700 motion-reduce:transition-none ${slide.theme} ${
                active ? 'opacity-100' : 'pointer-events-none opacity-0'
              }`}
            >
              {slide.image ? (
                <img src={slide.image} alt="" className="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover md:block" />
              ) : (
                <SlideArt variant={slide.art} />
              )}

              <div className="relative z-10 mx-auto flex h-full min-h-[26rem] max-w-6xl flex-col justify-center px-4 pb-20 md:min-h-[30rem] md:px-8">
                <div className="max-w-xl">
                  <h2 className="font-display text-4xl font-semibold leading-[1.05] tracking-tight md:text-6xl">
                    {slide.title}
                  </h2>
                  <p className="mt-4 max-w-md text-lg">{slide.text}</p>
                  <Link
                    to={slide.cta.to}
                    className={`mt-7 inline-block rounded-full px-7 py-3 font-semibold transition-colors ${slide.ctaClass}`}
                  >
                    {slide.cta.label}
                  </Link>
                </div>
              </div>
            </div>
          )
        })}
      </div>

      <div className="absolute bottom-5 left-4 z-20 flex items-center gap-1 rounded-full bg-white/95 px-2 py-1 text-night shadow-lg md:left-8">
        <button
          type="button"
          aria-label="Diapositive précédente"
          onClick={() => go(index - 1)}
          className="rounded-full p-1.5 hover:bg-night/10"
        >
          <ChevronLeft />
        </button>

        <div className="flex items-center">
          {slides.map((slide, position) => (
            <button
              key={slide.id}
              type="button"
              aria-label={`Aller à la diapositive ${position + 1}`}
              aria-current={position === index}
              onClick={() => go(position)}
              className="p-1.5"
            >
              <span
                className={`block h-2.5 rounded-full transition-all motion-reduce:transition-none ${
                  position === index ? 'w-6 bg-primary' : 'w-2.5 bg-night/30'
                }`}
              />
            </button>
          ))}
        </div>

        <button
          type="button"
          aria-label="Diapositive suivante"
          onClick={() => go(index + 1)}
          className="rounded-full p-1.5 hover:bg-night/10"
        >
          <ChevronRight />
        </button>

        <button
          type="button"
          aria-label={playing ? 'Mettre le défilement en pause' : 'Reprendre le défilement'}
          onClick={() => setPlaying((value) => !value)}
          className="ml-1 rounded-full p-2 hover:bg-night/10"
        >
          {playing ? <Pause className="size-4" /> : <Play className="size-4" />}
        </button>
      </div>
    </section>
  )
}