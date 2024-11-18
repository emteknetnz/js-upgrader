# js-upgrader

Quick script to identify and update package.json files

## Installation

```bash
cd vendor
mkdir emteknetnz
cd emteknetnz
git clone git@github.com:emteknetnz/js-upgrader.git
cd js-upgrader
```

## Usage

Generate a report to output.txt

```bash
php run.php
```

Generate a report to output.txt and do minor updates in package.json files

```bash
php run.php update
```

In every updated directories, starting with `silverstripe/admin` you'll need to then manually run

```bash
yarn install
yarn upgrade
yarn build
```
