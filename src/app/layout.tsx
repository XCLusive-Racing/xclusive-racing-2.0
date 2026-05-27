import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'XCLusive Racing - The Lion is Born to Dominate',
  description: 'XCLusive Esports - From console championships to global PC competition. Professional sim racing organization competing in ACC, Le Mans Ultimate, and iRacing.',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  )
}
