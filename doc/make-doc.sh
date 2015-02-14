#! /bin/bash

echo "Generating HTML and PDF for README.md..."
pandoc --from=markdown \
       --to=html5 \
       --self-contained \
       --output=README.html \
       README.md
pandoc --from=markdown \
       --to=latex \
       --output=README.pdf \
       README.md

cd comptes-rendus
for file in *.md
do
  echo "Generating HTML and PDF for ${file}..."
  pandoc --from=markdown \
         --to=html5 \
         --self-contained \
         --output=${file/.md/.html} \
         ${file}
  pandoc --from=markdown \
         --to=latex \
         --output=${file/.md/.pdf} \
         ${file}
done
cd ..

cd fiches
for file in *.md
do
  echo "Generating HTML and PDF for ${file}..."
  pandoc --from=markdown \
         --to=html5 \
         --self-contained \
         --output=${file/.md/.html} \
         ${file}
  pandoc --from=markdown \
         --to=latex \
         --output=${file/.md/.pdf} \
         ${file}
done
cd ..

cd presentations
for file in *.md
do
  echo "Generating HTML and PDF for ${file}..."
  filename=${file/.md/}
  pandoc --from=markdown \
         --to=slidy \
         --self-contained \
         --output=${file/.md/.html} \
         ${file}
  pandoc --from=markdown \
         --to=beamer \
         --output=${file/.md/.pdf} \
         ${file}
done
cd ..
