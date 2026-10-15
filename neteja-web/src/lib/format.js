const number = new Intl.NumberFormat('fr-FR')

/** 25000 devient « 25 000 FCFA ». */
export const fcfa = (amount) => `${number.format(amount)} FCFA`