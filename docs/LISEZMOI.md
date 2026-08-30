# Font Finder

[![Build Woff2 Decompress utility](https://github.com/ls-a-fr/php-font-finder/actions/workflows/woff2.yml/badge.svg)](https://github.com/ls-a-fr/php-font-finder/actions/workflows/woff2.yml)
[![Test and bench](https://github.com/ls-a-fr/php-font-finder/actions/workflows/test-and-bench.yml/badge.svg)](https://github.com/ls-a-fr/php-font-finder/actions/workflows/test-and-bench.yml)

Cette bibliothèque est un outil universel de recherche de polices : avec _presque tous_ les systèmes d'exploitation, pour _presque tous_ les formats de polices, renvoie un tableau d'informations standardisées. Elle se veut (très) rapide et récupère des informations minimales mais souvent suffisantes sur les polices.

Si vous appréciez ce package et souhaitez nous soutenir, ajoutez une étoile à ce projet : cela compte beaucoup pour nous !

## Exemple rapide

```php
$fonts = FontFileFinder::init()
    // Ajouter les polices système (dépend du système d'exploitation)
    ->addSystemFonts()
    // Ajouter un répertoire
    ->addDirectoryRecursive(implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "tests", "samples"]))
    ->get();
```


Dans l'exemple précédent, `$fonts` est un tableau d'objets `Font`, regroupés par nom de police, comportant les propriétés suivantes :
- `filename` : emplacement de la police
- `name` : nom de la police
- `bold` : indicateur de gras (booléen)
- `italic` : indicateur italique (booléen)
- `weight` : poids du style (normalisation CSS), de 100 à 900

Pour en savoir plus, consultez la [documentation ci-dessous](#documentation).

## Installation

Ce package est disponible sur Composer. Pour l'installer :
```sh
composer require ls-a/xsl-core
```

## Systèmes d'exploitations pris en charge

Ce package a été testé avec :
- Windows x64
- Windows i386
- Windows ARM
- macOS amd64
- macOS arm64
- Linux amd64
- Linux arm64
- Linux armv7
- FreeBSD amd64
- FreeBSD arm64
- OpenBSD amd64
- OpenBSD arm64
- Solaris amd64

Plus plus d'informations, consultez l'[action test-and-bench](.github/workflows/test-and-bench.yml).

## Formats de polices pris en charge

Cette bibliothèque récupère pour le moment les informations des polices au sein des formats suivants :
- AdobeFontMetrics (*.afm, in conjuction with PFA/PFB/T1/PS)
- BitstreamSpeedo (*.spd)
- BsdVgaFont (*.fnt, but BSD ones, not Microsoft)
- CompactFontFormat (*.cff, both PostScript and binary)
- DataForkTrueTypeFont (*.dfont)
- EmbeddedOpenType (*.eot)
- GlyphBitmapDistributionFormat (*.bdf)
- JimHersheyFont (*.jhf)
- OpenTypeBitmap (*.otb)
- OpenTypeCollection (*.otc)
- OpenTypeFont (*.otf)
- PcScreenFont (*.psf)
- PcScreenFontUnicodeCompressed (*.psfu.gz, usually found with *.psf.fz extension)
- PcScreenFontCompressed (*.psf.gz)
- PcScreenFontUnicode (*.psfu)
- PortableCompiledFormat (*.pcf)
- PortableCompiledFormatCompressed (*.pcf.gz)
- PostScript (*.ps)
- PrinterFontAscii (*.pfa)
- PrinterFontBinary (*.pfb)
- PrinterFontMetrics (*.pfm, in conjuction with PFA/PFB/T1/PS)
- ScalableVectorGraphics (*.svg)
- ScalableVectorGraphicsCompressed (*.svgz)
- Type1 (*.t1)
- TrueTypeCollection (*.ttc)
- TrueTypeFont (*.ttf)
- WebOpenFontFormat (*.woff)
- WebOpenFontFormat2 (*.woff2)
- WindowsBitmapFontCollection (*.fon)

## Pourquoi ?

Ce devait être une toute petite partie d'un outil plus général, et finalement il y avait bien davantage à faire qu'escompté. Nous devions vérifier certaines options de configuration pour notre prochain package consacré à XSL-FO, en particulier l'option `<auto-detect/>` dans le fichier de configuration d'Apache FOP. Cette option permet de récupérer toutes les polices enregistrées dans le système d'exploitation actuel.  

Au départ, nous voulions valider certaines valeurs par rapport à cette option. Puis, nous avons constaté que les anciennes polices sur certains systèmes d'exploitation ne mentionnaient pas leur nom dans le nom de fichier. Et après quelques recherches sur Composer, il semble qu'aucun paquet ne le fasse. [Phenx PHP Font Lib](https://packagist.org/packages/phenx/php-font-lib) gère les formats TrueType, OpenType et WOFF, mais c'est tout. Et il est malheureusement très lent avec des centaines de polices.  

Au fil du temps et en fouillant dans l'histoire des polices, ce paquet est devenu au cours du développement un hommage à l'évolution des polices. Et c'est un plaisir de découvrir tant de façons d'intégrer des caractères et de la typographie.

Si la curiosité vous gagne, découvrez comment nous avons procédé [ici](docs/DEROULEMENT.md).  
Si vous préférez consulter les polices utilisées comme exemples dans ce package, leurs sources et nos remerciements, rendez-vous [ici](samples/README.md).

## Performance

La performance est vérifiée avec les runners GitHub, dans l'[action test-and-bench](.github/workflows/test-and-bench.yml).  
Les données affichées ci-dessous ont été extraites pendant une seule itération d'un job, avec les architectures suivantes :
- Windows : Windows amd64
- MacOS : MacOS amd64
- Linux : Linux amd64
- FreeBSD : Machine virtuelle amd64 dans un conteneur Linux
- OpenBSD : Machine virtuelle amd64 dans un conteneur Linux
- Solaris : Machine virtuelle amd64 dans un conteneur Linux

**Note:** Logiquement, vous devriez obtenir de meilleurs résultats pour *BSD et Solaris avec une machine physique non émulée.

Les durées affichées sont *par fichier*: il s'agit d'une moyenne calculée pour chacune des polices disponibles dans le dossier `samples` ainsi que celles présentes sur le système cible.

| Extension   | Windows | Mac   | Linux | FreeBSD | OpenBSD | Solaris |
| ----------- | ------- | ----- | ----- | ------- | ------- | ------- |
| bdf         | 3ms     | 48µs  | 85µs  | 81µs    | 104µs   | 81µs    |
| cff         | 37µs    | 17µs  | 103µs | 32µs    | 39µs    | 19µs    |
| cff (ps)    | 2ms     | 621µs | 9ms   | 1ms     | 2ms     | 1ms     |
| dfont       | 129µs   | 49µs  | 405µs | 114µs   | 141µs   | 91µs    |
| eot         | 123µs   | 46µs  | 401µs | 96µs    | 118µs   | 79µs    |
| fnt (BSD)   | 5µs     | 2µs   | 7µs   | 3µs     | 4µs     | 3µs     |
| fon (win)   | 19µs    | 5µs   | 27µs  | 7µs     | 10µs    | 7µs     |
| jhf         | 4µs     | 2µs   | 5µs   | 2µs     | 3µs     | 2µs     |
| pcf         | 75µs    | 31µs  | 246µs | 57µs    | 70µs    | 50µs    |
| pcf.gz      | 80µs    | 43µs  | 230µs | 68µs    | 80µs    | 57µs    |
| pfa         | 60µs    | 36µs  | 41µs  | 24µs    | 75µs    | 58µs    |
| pfb         | 122µs   | 83µs  | 96µs  | 40µs    | 88µs    | 81µs    |
| psf         | 4µs     | 2µs   | 5µs   | 2µs     | 3µs     | 2µs     |
| psf.gz      | 7µs     | 5µs   | 9µs   | 6µs     | 7µs     | 6µs     |
| psfu        | 3µs     | 1µs   | 5µs   | 2µs     | 3µs     | 3µs     |
| psfu.gz     | 9µs     | 6µs   | 11µs  | 7µs     | 8µs     | 7µs     |
| spd         | 4µs     | 2µs   | 7µs   | 2µs     | 3µs     | 3µs     |
| svg         | 12µs    | 11µs  | 13µs  | 8µs     | 10µs    | 9µs     |
| svgz        | 129µs   | 132µs | 102µs | 117µs   | 121µs   | 105µs   |
| ttc         | 372µs   | 287µs | 2ms   | 483µs   | 549µs   | 397µs   |
| ttf         | 189µs   | 145µs | 1ms   | 272µs   | 327µs   | 214µs   |
| woff        | 5ms     | 3ms   | 7ms   | 5ms     | 5ms     | 5ms     |
| woff2       | 210ms   | 21ms  | 22ms  | 30ms    | 70ms    | 27ms    |

Vous pouvez vérifier les métriques dans [l'exécution réalisée spécifiquement pour ces métriques](https://github.com/ls-a-fr/php-font-finder/actions/runs/23244358247/job/67568710047).  

Si vous aimez PHPBench et préférez leurs métriques, parce que vous vous intéressez également à la consommation de mémoire vive, les voici :

### Windows amd64

| benchmark              | subject      | set | revs | its | mem_peak | mode    | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | ------- | ------ |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 1.946mb  | 7.184ms | ±0.61% |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 1.946mb  | 5.251μs | ±3.50% |

### Linux amd64

| benchmark              | subject      | set | revs | its | mem_peak | mode    | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | ------- | ------ |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 2.189mb  | 5.843μs | ±1.82% |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 2.189mb  | 1.645ms | ±0.29% |

### MacOS amd64
| benchmark              | subject      | set | revs | its | mem_peak | mode    | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | ------- | ------ |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 1.950mb  | 1.064ms | ±4.56% |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 1.950mb  | 3.099μs | ±9.82% |

### FreeBSD amd64 (VM)

| benchmark              | subject      | set | revs | its | mem_peak | mode      | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | --------- | ------ |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 1.955mb  | 346.581μs | ±1.02% |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 1.955mb  | 3.367μs   | ±9.12% |

### OpenBSD amd64 (VM)

| benchmark              | subject      | set | revs | its | mem_peak | mode    | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | ------- | ------ |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 1.943mb  | 3.870ms | ±1.69% |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 1.943mb  | 4.224μs | ±6.37% |

### Solaris amd64 (VM)

| benchmark              | subject      | set | revs | its | mem_peak | mode    | rstdev |
| ---------------------- | ------------ | --- | ---- | --- | -------- | ------- | ------ |
| SamplesPerformanceTest | benchSamples |     | 100  | 5   | 1.948mb  | 1.463ms | ±1.63% |
| SystemPerformanceTest  | benchSystem  |     | 100  | 5   | 1.948mb  | 3.821μs | ±3.65% |

## Documentation

Chaque appel à ce package devrait être réalisé sur la classe `FontFileFinder`.

### Usage

#### Créer une instance: inst()

```php
// Renvoie une instance de FontFileFinder
$instance = FontFileFinder::inst();
```

Aucune opération ne sera effectuée tant que vous n'appelez pas la méthode `get()`.


#### Inclure les polices du système d'exploitation : addSystemFonts()

```php
// Renvoie une instance de FontFileFinder
$instance = FontFileFinder::inst()
    ->addSystemFonts();
```

Ajoutera les dossiers enregistrés par votre système pour le stockage des polices.

#### Inclure un dossier spécifique: addDirectory()

```php
// Renvoie une instance de FontFileFinder
$instance = FontFileFinder::inst()
    // Conseil : utilisez un chemin absolu
    ->addDirectory(implode(DIRECTORY_SEPARATOR, [
        __DIR__,
        'samples'
    ]));
```

Cette méthode ajoutera les polices présentes dans le dossier spécifié. Attention, cette méthode n'est pas récursive.

#### Inclure un dossier spécifique, de manière récursive: addDirectoryRecursive()

```php
// Renvoie une instance de FontFileFinder
$instance = FontFileFinder::inst()
    // Conseil : utilisez un chemin absolu
    ->addDirectoryRecursive(implode(DIRECTORY_SEPARATOR, [
        __DIR__,
        'samples'
    ]));
```

Cette méthode ajoutera les polices présentes dans le dossier spécifié, de manière récursive.

#### Exclure des chemins par motifs : except()

```php
$instance = FontFileFinder::inst()
    // Exclure les fichiers correspondant à l'expression régulière (ex: .gitignore, .gitkeep)
    ->except('/\.git.*$/')
    // Vous pouvez aussi préciser vos motifs avec une liste
    ->except([
        '/myfolder\/*/',
        '/\.php$/'
    ]) ;
```

La méthode except accepte des expressions régulières ; celles-ci sont exécutées sur le chemin absolu du fichier.
Si vous préférez exclude via des chaînes de caractères, utilisez exceptExact.


#### Exclure des noms de fichier: exceptExact()

```php
$instance = FontFileFinder::inst()
    // Exclure les fichiers .gitignore
    ->exceptExact('.gitignore')
    // Vous pouvez aussi préciser vos fichiers avec une liste
    ->exceptExact([
        '.DS_Store',
        '.gitkeep'
    ]);
```

Cette méthode compare les chaînes de caractères spécifiées avec le chemin absolu des polices, **avec la méthode `str_ends_with`**. Cela signifie que vous pouvez exclure des extensions sans préciser des expressions régulières, qui sont souvent plus lentes.

#### Récupérer les polices

```php
// Récupérer les polices
$fonts = FontFileFinder::init()
    // Ajoute les polices de caractères du système
    ->addSystemFonts()
    // Ajoute un dossier
    ->addDirectoryRecursive(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'tests', 'samples']))
    ->get();
```

Vous pouvez également récupérer d'autres informations: le nombre de polices, et les erreurs. La méthode `get()` permet de spécifier les données que vous souhaitez :

```php
[$errors, $count, $fonts] = FontFileFinder::init()
    ->addSystemFonts()
    ->get('errors', 'count', 'fonts');
```

Pour le moment, seuls `errors`, `count` et `fonts` sont autorisés. Vous pouvez les préciser dans l'ordre de votre choix :

```php
[$count, $errors] = FontFileFinder::init()
    ->addSystemFonts()
    ->get('count', 'errors');
```

### Configuration

#### Cacher les erreurs

Par défaut, les erreurs sont affichées dans la sortie d'erreurs (stderr). Pour ne pas les afficher :

```php
$instance = FontFileFinder::inst()
    ->silent();
```

#### Activer les métriques de performance

Des fois, vous voulez voir les durées de traitement.

```php
$instance = FontFileFinder::inst()
    ->enableMetrics();
```

Vous pouvez les désactiver avec `disableMetrics`.

### Avancé

#### Utiliser un tableau de configuration

```php
$fonts = FontFileFinder::load([
     'directories' => [
         [
             'recursive' => true,
             'path' => '/usr/local/...'
         ],
         '/home/user/...'
     ]
]);
```

La méthode `load` accepte `autoDetect` (booléen) et `directories`. Ce n'est pas possible de récupérer les erreurs et le nombre de polices de cette manière : peut-être dans une prochaine version !

#### Vérifier les polices du système

Si vous voulez voir rapidement les polices du système d'exploitation, écrivez simplement :

```php
$fonts = FontFileFinder::getSystemFonts();
```

Vous n'aurez pas accès aux options de configuration de FontFileFinder, mais pour certains cas, c'est totalement suffisant.

#### Surcharger FontDecoder

FontFileFinder se base sur une classe nommée `FontDecoder`, qui:
- Exécute tous les décodeurs jusqu'à en trouver un correspondant au fichier de police actuel
- Crée les métriques de performance

Si cette classe n'est pas suffisante pour vos besoins, créez une nouvelle classe étendant FontDecoder, et déclaez-la comme suit :

```php
$instance = FontFileFinder::inst()
    ->setDecoderClass(MyAwesomeClass::class);
```

## Journal des modifications

Veuillez consulter le fichier [CHANGELOG](CHANGELOG.md) pour voir les dernières modifications.

## Support

Nous mettons du coeur à l'ouvrage pour proposer des produits de qualité et accessibles à toutes et tous. Si vous aimez notre travail, n'hésitez pas à faire appel à nous pour votre prochain projet !  

## Contributions

Les contributions sont régies par le fichier [CONTRIBUTING](https://github.com/ls-a-fr/.github/CONTRIBUTING.md).

## Sécurité

Si vous avez déniché un bug ou une faille, merci de nous contacter par mail à [mailto:contact@ls-a.fr](contact@ls-a.fr) en lieu et place d'une issue, pour respecter la sécurité des autres usagers.


## Crédits

- Renaud Berthier

## Licence

Code déposé sous licence MIT. Rendez-vous sur le fichier LICENSE pour davantage d'informations.