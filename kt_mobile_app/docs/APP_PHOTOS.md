# App photos

The ten owner-supplied photos are bundled under `assets/images/` as WebP
images (quality 85, original dimensions). The original PNG files are preserved
in the owner's graphics folder. The bundled copies total approximately 1.6 MB.

| Original filename | Bundled filename | Category |
| --- | --- | --- |
| Bus exterior image.png | executive-exterior.webp | Executive |
| Bus exterior front.png | executive-front.webp | Executive |
| interior seats frontside.png | executive-seats.webp | Executive |
| Interior seat backside.png | executive-seatbacks.webp | Executive |
| Sleeper exterior.png | sleeper-exterior.webp | Sleeper |
| Sleeper interior.png | sleeper-interior.webp | Sleeper |
| Sleeper bed.png | sleeper-bed.webp | Sleeper |
| Lounge 1.png | lounge-1.webp | Waiting Lounge |
| Lounge 2.png | lounge-2.webp | Waiting Lounge |
| lounge 3.png | lounge-3.webp | Waiting Lounge |

`lib/features/home/fleet_photos.dart` defines the presentation labels and photo
categories. Both the home carousel and Bus & seat guide use this catalog.
Bundled photos remain available while offline or while the existing fleet API
loads. Published API photos appear first, in their selected placements; the guide retains a
retry action for online failures. Exterior photos use containment to show the
whole bus; interior and lounge photos fill their frames.

These are presentation assets, not API records or assignments to particular
schedules, terminals or bookable vehicles. No backend changes are required.
The existing `assets/images/` declaration in `pubspec.yaml` includes the files.
Rebuild the application to distribute new or replaced bundled photos.


## Photos managed in the ERP

Fleet & Content now accepts uploaded pictures or public HTTPS URLs. Staff choose
one or more placements: Home banner (`home_banner`), Home photo carousel
(`home_carousel`), Bus & Seat Guide (`bus_guide`), or Home promotions
(`home_promotion`). Categories and bus classes remain available for guide filters.
The app calls the existing authenticated `GET /fleet?include_placements=1`
contract, supplied by the companion mobile-backend change. Only published,
company-visible content is returned. Banners/promotions are informational;
they do not imply a booking discount or change server fares.

Published images keep API order and precede bundled images. Bundled images stay
in the carousel and guide; banners and promotions have no offline substitutes.
Pull-to-refresh on Home reloads both bookings and media; the guide also refreshes
media. Media also reloads when the app resumes, and signing out clears the media
catalog so signing back in fetches it again. Successful catalog fetches evict the
returned image URLs from Flutter's image cache and re-resolve their image streams;
image downloads request cache revalidation so replacements at the same public URL
can update too. Failed Home media refreshes finish with a retry message, even when
the bookings request also fails. Broken image URLs show the existing image
placeholder. Hiding/removing an ERP image takes effect on the next content fetch; no new release is needed
for subsequent image changes after this placement-aware app version is installed.

Deploy the api branch's image-placement migration and storage link, the
mobile-backend gallery reader, and this mobile-frontend release together.
For real devices, uploaded URLs must use a public HTTPS host (or a reachable
LAN address for development), not localhost. Local ERP data does not update
a production app pointed at a different backend/database.
