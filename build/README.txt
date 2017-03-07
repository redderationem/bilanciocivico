# Readme

## Requisiti

Per usare BilancioCivico avrai bisogno di uno spazio web che supporti Apache 2+
e Php 7.0+. Avrai bisogno anche dell'estensione Apache mod_rewrite, 
dell'estensione Php PDO e del relativo driver per Sqlite. Se non sai come
verificare se il tuo spazio soddisfa i requisiti chiedi al fornitore.

## Installazione

Dopo aver verificato che i requisiti siano soddisfatti potrai procedere
all'installazione di BilancioCivico. La procedura è semplicissima: ti basterà
copiare sul tuo spazio tutti i file contenuti in questa cartella.

## Configurazione

Copiati i file, potrai configurare BilancioCivico col nome del
tuo comune etc visitando l'indirizzo 
[tuospazioweb]/amministrazione/configurazione.html. Immetti "admin" sia come
nome utente sia come password quando ti sarà richiesto. Per salvare la 
configurazione dovrai specificare anche la password, che verrà aggiornata
se diversa da quella precedente.

## Importazione dei dati

A questo punto potrai importare i dati del bilancio del tuo comune
visitando l'indirizzo [tuospazioweb]/amministrazione/importazione.html.
I dati da importare devono essere contenuti in un file xml compatibile
con lo standard di BilancioCivico. Troverai i dettagli dello standard, un
modello di file xml compatibile e alcuni modelli di file excel che consentono
di generare automaticamente un file xml compatibile all'indirizzo
www.bilanciocivico.it/documentazione.html.

## Riparazione

Se dopo aver configurato il sito o aver importato i dati incontrerai qualche
problema esegui la riparazione automatica visitando l'indirizzo 
[tuospazioweb]/amministrazione/riparazione.html.

## Aggiornamento

Ogni tanto BilancioCivico sarà aggiornato con nuove funzionalità. Per mantenere
aggiornata la tua installazione dovrai scaricare l'ultima versione di
BilancioCivico e copiare tutti i file contenuti nella cartella sul tuo spazio
web, sovrascrivendo i file esistenti. L'operazione non comporta rischi: la tua
configurazione e i dati importati sono al sicuro.

## Licenza

BilancioCivico è distribuito sotto la licenza AGPL 3. Una copia della licenza è
disponibile in questa cartella. L'Associazione di promozione Sociale Redde
Rationem non si assume alcuna responsabilità nei confronti del programma e
dell'uso che ne può essere fatto. Si rimanda al riguardo al punto 15 della
licenza.

## Contribuisci al progetto

Se vuoi contribuire allo sviluppo di BilancioCivico o sostenerlo con una
donazione visita l'indirizzo [indirizzo]. Abbiamo bisogno dell'aiuto di tutti!