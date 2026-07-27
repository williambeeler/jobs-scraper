# Google Places Tiers

This file is a quick reference for Google Places API field tiers.

Scope: `Place Details (New)` in the Places API.

Important note. Google also has `Text Search (New)` and `Nearby Search (New)`. Some fields land in different SKUs for those endpoints, so this file should be treated as the `Place Details (New)` reference unless noted otherwise.

## How Google bills

Google bills Places requests by the highest SKU tier triggered by the fields in your field mask.

Examples:

1. If you request only `formattedAddress` and `postalAddress`, the request stays in `Essentials`.
2. If you add `displayName`, the request becomes `Pro`.
3. If you add `websiteUri`, the request becomes `Enterprise`.

## Essentials IDs Only

Use this tier when you only need identifiers or lightweight metadata.

| Field | What it gives you |
| --- | --- |
| `attributions` | Attribution text and provider credits |
| `id` | Google Place ID |
| `moved_place` | Indicates the place moved |
| `moved_place_id` | New place ID if the place moved |
| `name` | Resource name such as `places/PLACE_ID` |
| `photos` | Photo metadata only |

Important note. `name` is not the human readable business name. If you want the actual business name, use `displayName`, which is in `Pro`.

## Essentials

Use this tier when you need address and location data.

| Field | What it gives you |
| --- | --- |
| `addressComponents` | Structured address parts such as city, state, postal code |
| `addressDescriptor` | Address descriptor data, mostly relevant in India |
| `adrFormatAddress` | Address in ADR microformat |
| `formattedAddress` | Formatted full address |
| `location` | Latitude and longitude |
| `plusCode` | Plus Code location identifier |
| `postalAddress` | Structured postal address |
| `shortFormattedAddress` | Shorter formatted address |
| `types` | Place categories |
| `viewport` | Suggested map viewport bounds |

Good fit for:

1. Address lookup
2. Map pinning
3. City, state, and ZIP extraction

## Pro

Use this tier when you need the real business identity and richer place metadata.

| Field | What it gives you |
| --- | --- |
| `accessibilityOptions` | Accessibility related information |
| `businessStatus` | Operational, temporarily closed, or permanently closed |
| `containingPlaces` | Parent or containing places |
| `displayName` | Human readable business name |
| `googleMapsLinks` | Google Maps related links |
| `googleMapsUri` | Direct Google Maps URL |
| `iconBackgroundColor` | Suggested icon background color |
| `iconMaskBaseUri` | Base URI for place icon assets |
| `primaryType` | Primary place category |
| `primaryTypeDisplayName` | Human readable primary category |
| `pureServiceAreaBusiness` | Whether the business is service area only |
| `subDestinations` | Sub destinations or nested places |
| `utcOffsetMinutes` | Local UTC offset |

Good fit for:

1. Business name
2. Operational status
3. Category labels
4. Google Maps links

## Enterprise

Use this tier when you need contact details, ratings, hours, or website data.

| Field | What it gives you |
| --- | --- |
| `currentOpeningHours` | Current opening hours |
| `currentSecondaryOpeningHours` | Current secondary hours |
| `internationalPhoneNumber` | International phone number |
| `nationalPhoneNumber` | National format phone number |
| `priceLevel` | General price level |
| `priceRange` | Price range information |
| `rating` | Average rating |
| `regularOpeningHours` | Standard opening hours |
| `regularSecondaryOpeningHours` | Standard secondary hours |
| `userRatingCount` | Number of ratings |
| `websiteUri` | Website URL |

Important note. If you request `websiteUri`, the request is no longer `Pro`. It becomes `Enterprise`.

## Enterprise + Atmosphere

Use this tier when you want amenity, service, dining, or vibe style data.

| Field | What it gives you |
| --- | --- |
| `allowsDogs` | Whether dogs are allowed |
| `curbsidePickup` | Curbside pickup availability |
| `delivery` | Delivery availability |
| `dineIn` | Dine in availability |
| `editorialSummary` | Google editorial summary |
| `evChargeOptions` | EV charging details |
| `fuelOptions` | Fuel options |
| `generativeSummary` | AI generated place summary |
| `goodForChildren` | Child friendly indicator |
| `goodForGroups` | Group friendly indicator |
| `goodForWatchingSports` | Sports viewing suitability |
| `liveMusic` | Live music availability |
| `menuForChildren` | Children menu availability |
| `neighborhoodSummary` | AI powered neighborhood summary |
| `outdoorSeating` | Outdoor seating availability |
| `parkingOptions` | Parking details |
| `paymentOptions` | Payment method details |
| `reservable` | Reservation availability |
| `restroom` | Restroom availability |
| `reviews` | Reviews data |
| `reviewSummary` | Review summary |
| `routingSummaries` | Routing related summaries |
| `servesBeer` | Beer service |
| `servesBreakfast` | Breakfast service |
| `servesBrunch` | Brunch service |
| `servesCocktails` | Cocktail service |
| `servesCoffee` | Coffee service |
| `servesDessert` | Dessert service |
| `servesDinner` | Dinner service |
| `servesLunch` | Lunch service |
| `servesVegetarianFood` | Vegetarian options |
| `servesWine` | Wine service |
| `takeout` | Takeout availability |

Good fit for:

1. Restaurant discovery
2. Hospitality and venue filtering
3. Rich consumer facing place pages

## Quick guidance for this CRM

If you only need:

1. Business name
2. Address, city, state, ZIP
3. No website

Use:

1. `displayName`
2. `addressComponents`
3. `formattedAddress` or `postalAddress`

That puts you in `Pro`.

If you also need:

1. `websiteUri`

That moves you to `Enterprise`.

## Recommended field sets

### Cheapest useful business profile

`displayName,addressComponents,formattedAddress`

Tier: `Pro`

### Structured address focused

`displayName,postalAddress,addressComponents`

Tier: `Pro`

### Business profile with website

`displayName,addressComponents,formattedAddress,websiteUri`

Tier: `Enterprise`

## Sources

1. [Place Data Fields (New)](https://developers.google.com/maps/documentation/places/web-service/data-fields)
2. [Place Details (New)](https://developers.google.com/maps/documentation/places/web-service/place-details)
3. [Choose fields to return](https://developers.google.com/maps/documentation/places/web-service/choose-fields)
