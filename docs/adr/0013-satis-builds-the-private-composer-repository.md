# Satis builds the private Composer repository

Aura Store will use Satis to read private plugin repositories and generate Composer metadata, checksummed immutable distribution archives, and S3-compatible archive URLs. Satis remains an internal build tool: `aura-cms.com` authenticates each Composer Credential, filters metadata to active Entitlements, and authorizes archive downloads, so customer, billing, license, and project rules never live in the static repository generator.
