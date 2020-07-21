#!/bin/bash

DATESTR=gexf-`date +%Y%m%d`

mkdir -p $DATESTR

php gexf-aq-female.php 1 1844-07-01 > $DATESTR/1844-1-matriarchal.gexf
php gexf-aq-female.php 1 1845-12-10 > $DATESTR/1845-1-matriarchal.gexf
php gexf-aq-female.php 1 1846-03-01 > $DATESTR/1846-1-matriarchal.gexf
php gexf-aq-female.php 5 1844-07-01 > $DATESTR/1844-5-matriarchal.gexf
php gexf-aq-female.php 5 1845-12-10 > $DATESTR/1845-5-matriarchal.gexf
php gexf-aq-female.php 5 1846-03-01 > $DATESTR/1846-5-matriarchal.gexf
php gexf-aq-male.php 1 1844-07-01 > $DATESTR/1844-1-patriarchal.gexf
php gexf-aq-male.php 1 1845-12-10 > $DATESTR/1845-1-patriarchal.gexf
php gexf-aq-male.php 1 1846-03-01 > $DATESTR/1846-1-patriarchal.gexf
php gexf-aq-male.php 5 1844-07-01 > $DATESTR/1844-5-patriarchal.gexf
php gexf-aq-male.php 5 1845-12-10 > $DATESTR/1845-5-patriarchal.gexf
php gexf-aq-male.php 5 1846-03-01 > $DATESTR/1846-5-patriarchal.gexf
php gexf-aq-binary.php 1 1844-07-01 > $DATESTR/1844-1-binary.gexf
php gexf-aq-binary.php 1 1845-12-10 > $DATESTR/1845-1-binary.gexf
php gexf-aq-binary.php 1 1846-03-01 > $DATESTR/1846-1-binary.gexf
php gexf-aq-binary.php 5 1844-07-01 > $DATESTR/1844-5-binary.gexf
php gexf-aq-binary.php 5 1845-12-10 > $DATESTR/1845-5-binary.gexf
php gexf-aq-binary.php 5 1846-03-01 > $DATESTR/1846-5-binary.gexf
