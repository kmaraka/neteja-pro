export const slides = [
  {
    id: 'maison',
    title: 'Une maison qui sent le propre',
    text: "Produits d'entretien, matériel et conseils livrés chez vous à Abidjan.",
    cta: { label: 'Voir la boutique', to: '/boutique' },
    theme: 'bg-night text-white',
    ctaClass: 'bg-citron text-night hover:bg-citron/90',
    art: 'bubbles',
  },
  {
    id: 'livraison',
    title: "Commandez aujourd'hui, payez à la livraison",
    text: "Livraison en 24 à 48 h dans les communes d'Abidjan. Carte bancaire et Mobile Money acceptés.",
    cta: { label: 'Commander maintenant', to: '/boutique' },
    theme: 'bg-citron text-night',
    ctaClass: 'bg-night text-white hover:bg-night/90',
    art: 'rings',
  },
  {
    id: 'jardin',
    title: 'Le jardin aussi mérite des soins',
    text: 'Sécateurs, arrosoirs et accessoires pour un extérieur soigné.',
    cta: { label: 'Voir le jardinage', to: '/boutique?category=jardinage' },
    theme: 'bg-accent text-night',
    ctaClass: 'bg-night text-white hover:bg-night/90',
    art: 'leaves',
    // Avec une vraie photo : image: '/images/jardin.jpg' (elle remplace les formes sur grand écran).
  },
]