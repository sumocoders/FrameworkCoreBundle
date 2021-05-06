# Known issues

## npm install failing

Downgrade node to version 0.10.36. Node did backwards incompatible changes making
npm install fail on execSync.

## generation of fonts failing

* Install the needed dependencies: `brew install batik fontforge ttfautohint ttf2eot`
* Install java development runtime: http://www.oracle.com/technetwork/java/javase/downloads/jdk8-downloads-2133151.html
