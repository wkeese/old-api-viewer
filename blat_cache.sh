#!/bin/bash
echo "Removing cached files from data/$1/cache"
find "data/$1/cache" -type f -print0 | xargs -0 rm
