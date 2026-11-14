/** Format server date-only values without relying on browser ISO timestamp parsing. */
export function formatGalleryDate(value) {
  if (!value) return null
  const match = /^(\d{4})-(\d{2})-(\d{2})(?:$|T)/.exec(String(value))
  if (!match) return null
  const year = Number(match[1]), month = Number(match[2]), day = Number(match[3])
  const date = new Date(Date.UTC(year, month - 1, day))
  if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return null
  try { return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(date) }
  catch { return `${match[3]}-${match[2]}-${match[1]}` }
}
