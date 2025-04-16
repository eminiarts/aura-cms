#!/bin/bash

output_file="_all.md"
base_dir="$(pwd)"

: > "$output_file"  # Clear the output file

# Included paths
INCLUDED_PATHS=(
  "./config"
  "./database"
  "./resources/stubs"
  "./resources/views"
  "./routes"
  "./src"
  "./tests"
  "./composer.json"
  "./package.json"
  "./vite.config.js"
  "./readme.md"
)

# Generate <file_map>
echo "<file_map>" >> "$output_file"

for path in "${INCLUDED_PATHS[@]}"; do
  if [ -d "$path" ]; then
    find "$path" -type f ! -name ".DS_Store" | while read -r file; do
      rel_path="${file#./}"
      echo "$rel_path" >> "$output_file"
    done
  elif [ -f "$path" ]; then
    rel_path="${path#./}"
    echo "$rel_path" >> "$output_file"
  fi
done

echo "</file_map>" >> "$output_file"
echo "" >> "$output_file"

# Generate <file_contents>
echo "<file_contents>" >> "$output_file"
echo "" >> "$output_file"

for path in "${INCLUDED_PATHS[@]}"; do
  if [ -d "$path" ]; then
    find "$path" -type f ! -name ".DS_Store" | while read -r file; do
      rel_path="${file#./}"
      echo "File: $rel_path" >> "$output_file"
      echo '```' >> "$output_file"
      cat "$file" >> "$output_file"
      echo '```' >> "$output_file"
      echo "" >> "$output_file"
    done
  elif [ -f "$path" ]; then
    rel_path="${path#./}"
    echo "File: $rel_path" >> "$output_file"
    echo '```' >> "$output_file"
    cat "$path" >> "$output_file"
    echo '```' >> "$output_file"
    echo "" >> "$output_file"
  fi
done

echo "</file_contents>" >> "$output_file"
