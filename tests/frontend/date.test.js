import test from 'node:test'
import assert from 'node:assert/strict'
import { formatGalleryDate } from '../../resources/js/gallery/utils/date.js'

test('formats Laravel timestamps with microseconds as a date without browser timestamp parsing', () => {
  assert.equal(formatGalleryDate('2026-11-14T00:00:00.000000Z'), '14 Nov 2026')
  assert.equal(formatGalleryDate('2026-11-14'), '14 Nov 2026')
})

test('invalid dates do not prevent the gallery from rendering', () => {
  assert.equal(formatGalleryDate('not-a-date'), null)
  assert.equal(formatGalleryDate('2026-02-30'), null)
  assert.equal(formatGalleryDate(null), null)
})
