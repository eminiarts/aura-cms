# Commercial Plugins have no hard dependencies on each other

Every Commercial Plugin must remain independently installable and useful because each is sold separately. Plugins may expose Optional Plugin Integrations, and the Aura Pro Bundle may provide a metapackage that installs all entitled packages, but no Commercial Plugin may require another Commercial Plugin; we accept some duplicated seams and graceful-degradation code in exchange for clear product boundaries and buyer choice.
