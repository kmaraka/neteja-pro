/** Transforme une erreur axios en { status, code, message, fields, hasFields }. */
export function parseError(error) {
  if (!error.response) {
    return {
      status: 0,
      code: 'network',
      message: 'Impossible de joindre le serveur. Vérifiez votre connexion.',
      fields: {},
      hasFields: false,
    }
  }

  const { status, data } = error.response
  const fields = data?.errors ?? {}

  let message = data?.message ?? 'Une erreur est survenue. Réessayez.'
  if (status === 429) message = 'Trop de tentatives. Réessayez dans une minute.'
  if (status === 403 && data?.code === 'email_not_verified') {
    message = 'Veuillez vérifier votre email avant de vous connecter.'
  }

  return { status, code: data?.code, message, fields, hasFields: Object.keys(fields).length > 0 }
}

/** Premier message d'erreur d'un champ (ou undefined). */
export const fieldError = (fields, name) => fields?.[name]?.[0]

/** Premier message lisible d'une erreur analysée : celui d'un champ, sinon le message général. */
export function firstMessage(parsed) {
  const firstField = Object.values(parsed.fields ?? {})[0]
  return firstField?.[0] ?? parsed.message
}