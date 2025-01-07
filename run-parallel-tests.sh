#!/bin/bash

# Clean up any existing test databases
for db in $(psql -l | grep testing_ | cut -d'|' -f1); do
    dropdb "$db"
done

# Run tests in parallel with proper isolation
TEST_TOKEN=$(date +%s) vendor/bin/pest --parallel --processes=4
