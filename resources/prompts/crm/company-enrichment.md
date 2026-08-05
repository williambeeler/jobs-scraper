Enrich this company record. Return ONLY fields you can infer confidently.

Rules:
- PRIORITY ORDER: website, phone, address.
- Always include website and phone keys in output (set null when unknown).
- Website discovery/search strategy:
  - Use the company name as the first and primary search term.
  - Include the full address context in the search intent: address_line1, address_line2, city, state, postal_code, country.
  - Prefer the result that best matches BOTH the company name and full address context.
  - Prioritize official business domains over directories/aggregators.
- Prefer the official main website when available.
- If no official website exists, use a Facebook page or equivalent site (like Wix) as website.
- If website candidates are provided, choose the best official website from them when possible.
- If an address appears as a single full line, split it into address_line1/city/state/postal_code/country.
- Normalize United States country values to USA.
- Leave unknown fields null.

Website candidates (search-ranked):
{{website_candidates}}

Company JSON:
{{company_json}}
