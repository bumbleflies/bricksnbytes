// Detail texts for the pop-up ("Mehr erfahren") of every tile on /kurse, /schulprojekttage,
// /geburtstage and /vorschule-hort — the single place to edit them.
//
// Key = tile file under src/content/tiles/ without ".yaml" (e.g. "kurse/programmierkurse").
// Alter, Dauer, Material and "Das nehmen die Kinder mit" come from the tile's own bullets.
//
// Texts are taken from the shop (pretix). Dates, times, places and prices are kept only in
// pretix; the pop-up links there. Anything still missing starts with "TODO:" — it is
// highlighted in `npm run dev` and left out of the live site until filled in.

export interface KartenDetails {
  // Introduction, one string per paragraph
  intro: string[];
  // Only for request offers (no pretix page): when and where it takes place
  termine?: string;
  ort?: string;
  // "Was dich erwartet"
  inhalte: { titel?: string; text: string }[];
  // "Warum …?" section of the overview page
  lernziele: { titel: string; absaetze: string[] };
  // Overrides the age when the tile has no age bullet
  alter?: string;
  // Small print, e.g. how booking works
  hinweis?: string;
}

const GUTSCHEIN = 'Du hast einen Gutschein? Den kannst du direkt bei der Buchung einlösen.';

const OFFEN = {
  inhalte: [{ text: 'TODO: Inhalte ergänzen' }],
  lernziele: { titel: 'Das lernen die Kinder', absaetze: ['TODO: Lernziele ergänzen'] },
};

const ANFRAGE = {
  termine: 'Euer Wunschtermin – am besten 4–6 Wochen im Voraus anfragen',
};

export const kartendetails: Record<string, KartenDetails> = {
  // https://shop.bricksnbytes.de/programierkurs/
  'kurse/programmierkurse': {
    intro: [
      'Dein Kind hat Lust, so richtig ins Programmieren einzusteigen? In unseren Programmierkursen treffen sich die Kinder regelmäßig einmal pro Woche und bauen Woche für Woche ihr Wissen aus.',
      'Anders als bei unseren Workshops geht es hier nicht nur ums Reinschnuppern: Über mehrere Termine hinweg lernen die Kinder Schritt für Schritt dazu, entwickeln eigene Projekte und merken, wie viel sie schon können. Vorkenntnisse braucht es keine.',
    ],
    inhalte: [
      { titel: 'LEGO Spike', text: 'Roboter bauen und programmieren, mit wechselnden Themen wie dem „Fabelhaften Freizeitpark“' },
      { titel: 'Minecraft Education', text: 'Programmieren, KI, Cybersicherheit und mehr, mitten in der Minecraft-Welt' },
      { titel: 'Feste Gruppe', text: 'immer die gleichen Kinder, die gleiche Uhrzeit und der gleiche Ort' },
      { titel: 'Mit Folgekurs', text: 'Wer dranbleiben will, kann direkt im nächsten Kurs weitermachen' },
    ],
    lernziele: {
      titel: 'Warum ein regelmäßiger Programmierkurs?',
      absaetze: [
        'Ein Workshop weckt die Neugier, ein regelmäßiger Kurs macht daraus echtes Können. Wenn Kinder jede Woche wiederkommen, bauen sie auf dem auf, was sie schon gelernt haben, trauen sich an größere Projekte und erleben, wie sie immer sicherer werden.',
        'Ganz nebenbei trainiert dein Kind logisches Denken, Kreativität und Durchhaltevermögen. Das hilft nicht nur beim Programmieren, sondern auch in Mathe, in der Schule und im Alltag. Und weil wir mit LEGO und Minecraft arbeiten, freut sich dein Kind jede Woche aufs Neue auf den Kurs.',
      ],
    },
    hinweis: `Mit einer Buchung meldest du dein Kind für alle Termine des Kurses an. ${GUTSCHEIN}`,
  },

  // https://shop.bricksnbytes.de/rynml/ — the overview page has no text; taken from the
  // description of the "Game Design mit PictoBlox" course in pretix
  'kurse/online': {
    intro: [
      'Dein Kind zockt gern und fragt sich, wie Spiele eigentlich gemacht werden? Dann ab in unseren Game-Design-Kurs! In mehreren Online-Terminen programmieren die Jugendlichen mit PictoBlox ihr eigenes Computerspiel.',
      'PictoBlox ist eine kostenlose, blockbasierte Programmierumgebung, perfekt für den Einstieg ins Coden und in die KI. Am Ende hat jede*r ein eigenes, funktionierendes Spiel, das sie oder er mit nach Hause nimmt.',
    ],
    inhalte: [
      { text: 'Figuren (Sprites) bewegen und per Tastatur steuern' },
      { text: 'Punkte zählen, Kollisionen erkennen, Level bauen' },
      { text: 'Gegner und Soundeffekte einbauen' },
    ],
    lernziele: {
      titel: 'Das lernt dein Kind',
      absaetze: ['Ganz nebenbei: Schleifen, Bedingungen und Variablen verstehen.'],
    },
    hinweis: 'Dein Kind braucht einen Laptop oder PC. PictoBlox ist kostenlos und läuft direkt im Browser. Eine Maus macht das Programmieren am Laptop deutlich angenehmer.',
  },

  // https://shop.bricksnbytes.de/73ps9-2/
  'kurse/workshops': {
    intro: [
      'Dein Kind ist neugierig, tüftelt gern und will wissen, wie Technik funktioniert? Dann bist du hier genau richtig!',
      'Unsere Workshops sind einmalige Programmiererlebnisse: kein fester Kurs über Wochen, sondern ein paar Stunden voller Ausprobieren, Bauen und Aha-Momente. Perfekt, um reinzuschnuppern und herauszufinden, ob Programmieren das Ding deines Kindes ist. Vorkenntnisse braucht es keine, nur Lust aufs Ausprobieren.',
    ],
    inhalte: [
      { titel: 'LEGO Spike', text: 'bauen, programmieren und zusehen, wie sich das eigene Modell bewegt' },
      { titel: 'Minecraft Education', text: 'vom Spieler zum Macher werden und die Minecraft-Welt mit Code gestalten' },
      { titel: 'Mädchen-Workshops', text: 'in kleiner Runde, ohne Druck und mit viel Platz für eigene Ideen' },
    ],
    lernziele: {
      titel: 'Warum Programmieren für Kinder?',
      absaetze: [
        'Kinder wachsen mit Tablets, Apps und Games auf, aber die wenigsten wissen, was dahintersteckt. Bei uns drehen wir den Spieß um: Dein Kind nutzt Technik nicht nur, sondern gestaltet sie selbst. Dabei lernt es, Probleme in kleine Schritte zu zerlegen, logisch zu denken und dranzubleiben, wenn etwas nicht gleich klappt.',
        'Das hilft nicht nur beim Programmieren, sondern auch in Mathe, in der Schule und im Alltag. Und weil wir mit Dingen arbeiten, die Kinder kennen und lieben, fühlt sich das Ganze überhaupt nicht nach Lernen an.',
      ],
    },
    hinweis: GUTSCHEIN,
  },

  // https://shop.bricksnbytes.de/73ps9/
  'kurse/ferienkurse': {
    intro: [
      'Ferien und keine Ahnung, was dein Kind machen soll? Wie wär’s mit einem Tag voller Technik, Kreativität und Teamwork!',
      'In unseren Ferienkursen entdecken Kinder das Programmieren ganz spielerisch, mit LEGO-Robotern, Minecraft und für die Größeren auch mit Python. Jeder Ferienkurs ist ein einmaliges Programmiererlebnis: ein ganzer Tag zum Bauen, Tüfteln und Ausprobieren, ganz ohne Vorkenntnisse.',
    ],
    inhalte: [
      { titel: 'LEGO Spike Essential', text: 'echte Roboter bauen und programmieren' },
      { titel: 'Minecraft Education', text: 'die Minecraft-Welt mit Code gestalten' },
      { titel: 'Python', text: 'für alle, die schon mal „richtigen“ Code schreiben wollen' },
      { titel: 'Rundum versorgt', text: 'Mittagessen, Wasser, Material und Laptops sind inklusive' },
    ],
    lernziele: {
      titel: 'Warum ein Programmier-Ferienkurs?',
      absaetze: [
        'In den Ferien haben Kinder endlich Zeit, sich richtig in etwas reinzufuchsen, ohne Hausaufgaben und ohne Zeitdruck. Genau das nutzen wir: Einen ganzen Tag lang wird gebaut, programmiert und ausprobiert, bis der Roboter fährt oder die Minecraft-Welt macht, was sie soll.',
        'Dabei lernt dein Kind ganz nebenbei, logisch zu denken, Probleme in kleine Schritte zu zerlegen und im Team zusammenzuarbeiten. Und abends kommt es nach Hause mit einer Urkunde und einer Menge zu erzählen.',
      ],
    },
    hinweis: GUTSCHEIN,
  },

  // https://shop.bricksnbytes.de/u8amk/
  'kurse/eltern-kind': {
    intro: [
      'Gemeinsam tüfteln, bauen und programmieren: In unseren Eltern-Kind-Workshops entdeckst du zusammen mit deinem Kind die Welt des Programmierens, ganz spielerisch mit LEGO Spike. Ob Mama, Papa, Oma oder Opa: Hier seid ihr ein Team!',
      'Die Workshops sind einmalige Programmiererlebnisse: kein fester Kurs über Wochen, sondern ein paar gemeinsame Stunden voller Bauen, Ausprobieren und Aha-Momente. Vorkenntnisse braucht ihr beide nicht, nur Lust aufs Tüfteln.',
    ],
    inhalte: [
      { titel: 'Kurze Einführung', text: 'ihr lernt LEGO Spike kennen und wisst, wie alles funktioniert' },
      { titel: 'Gemeinsames Projekt', text: 'spielerisch die Grundlagen des Programmierens entdecken' },
      { titel: 'Eure eigenen Ideen', text: 'vom flotten Rennauto bis zur kreativen Mini-Challenge' },
    ],
    lernziele: {
      titel: 'Warum ein Eltern-Kind-Workshop?',
      absaetze: [
        'Kleine Kinder lernen am besten mit jemandem an ihrer Seite, dem sie vertrauen. Im Eltern-Kind-Workshop entdeckt ihr Programmieren gemeinsam, und das ist für beide Seiten spannend: Dein Kind erlebt, dass es Technik selbst steuern kann, und du bekommst einen Einblick, wie spielerisch der Einstieg ins Programmieren sein kann.',
        'Ganz nebenbei verbringt ihr richtig schöne Zeit zusammen, ohne Bildschirm zum Berieseln, dafür mit Bauen, Ausprobieren und gemeinsamen Erfolgserlebnissen. Und wer weiß: Vielleicht tüftelt ihr zu Hause einfach weiter.',
      ],
    },
    hinweis: `Ein Ticket gilt für ein Kind und einen Erwachsenen. ${GUTSCHEIN}`,
  },

  // https://shop.bricksnbytes.de/9n7m8/ — intro and contents from the course description in
  // pretix, the overview page only has the "Warum …?" text
  'kurse/medienfuehrerschein': {
    intro: [
      'Dein Kind ist schon viel online, schaut Videos, chattet oder zockt? Dann ist jetzt der perfekte Zeitpunkt für den Medienführerschein!',
      'In drei Stunden lernen die Kinder spielerisch, wie sie sich sicher im Internet bewegen: was man teilen darf und was nicht, wie man Fakes erkennt und was man tut, wenn online mal etwas komisch läuft. So kann dein Kind das Internet mit gutem Gefühl nutzen und du kannst etwas entspannter sein.',
    ],
    inhalte: [
      { text: 'Sichere Passwörter bauen und persönliche Daten schützen' },
      { text: 'Fake News, Fake-Profile und Abzock-Tricks erkennen' },
      { text: 'Fair miteinander umgehen im Chat und wissen, was bei Cybermobbing hilft' },
      { text: 'Werbung, In-App-Käufe und Kostenfallen durchschauen' },
      { text: 'Fotos und Videos: Was darf ich posten, und was lieber nicht?' },
    ],
    lernziele: {
      titel: 'Warum ein Medienführerschein?',
      absaetze: [
        'Bevor Kinder alleine Fahrrad fahren, lernen sie die Verkehrsregeln. Im Internet ist es genauso, nur dass die meisten Kinder einfach loslegen. Sie schauen Videos, chatten, zocken und stoßen dabei schnell auf Situationen, die sie noch nicht einschätzen können: fremde Kontakte, Fake News, Kostenfallen oder gemeine Kommentare.',
        'Im Medienführerschein lernt dein Kind, sich sicher, selbstbewusst und fair im Netz zu bewegen. Es erfährt, wie man seine Daten schützt, Tricks und Fakes erkennt und was man tun kann, wenn online etwas komisch läuft. So wird aus „Ich klick halt mal drauf“ ein „Moment, das check ich erst mal“, und du kannst deinem Kind mit einem besseren Gefühl mehr zutrauen.',
      ],
    },
  },

  // Request-only offers: no shop page, texts from the offer pages themselves
  'schulprojekttage/projekttag': {
    intro: ['Programmieren für die ganze Klasse – direkt an eurer Schule.'],
    ...ANFRAGE,
    ort: 'Bei euch an der Schule – Material und Technik bringe ich komplett mit, ihr stellt nur den Raum.',
    ...OFFEN,
  },

  'vorschule-hort/workshop': {
    intro: ['Programmieren spielerisch entdecken – wir kommen zu euch in Kita, Vorschule oder Hort.'],
    ...ANFRAGE,
    ort: 'Bei euch in der Einrichtung – Material und Technik bringe ich komplett mit, ihr stellt nur den Raum.',
    ...OFFEN,
  },

  'geburtstage/mini-coder': {
    intro: ['Die Party, bei der Roboter mitfeiern.'],
    alter: 'TODO: Alter für dieses Paket ergänzen (Seite: allgemein 6–14 Jahre)',
    ...ANFRAGE,
    ort: 'Bei euch – Material und Technik bringe ich komplett mit, ihr stellt nur den Raum.',
    ...OFFEN,
    hinweis: 'Jedes Kind bekommt eine Urkunde, auf Wunsch mit kleinem LEGO-Geschenk.',
  },

  'geburtstage/code-builders': {
    intro: ['Die Party, bei der Roboter mitfeiern.'],
    alter: 'TODO: Alter für dieses Paket ergänzen (Seite: allgemein 6–14 Jahre)',
    ...ANFRAGE,
    ort: 'Bei euch – Material und Technik bringe ich komplett mit, ihr stellt nur den Raum.',
    ...OFFEN,
    hinweis: 'Jedes Kind bekommt eine Urkunde, auf Wunsch mit kleinem LEGO-Geschenk.',
  },

  'geburtstage/robo-masters': {
    intro: ['Die Party, bei der Roboter mitfeiern.'],
    alter: 'TODO: Alter für dieses Paket ergänzen (Seite: allgemein 6–14 Jahre)',
    ...ANFRAGE,
    ort: 'Bei euch – Material und Technik bringe ich komplett mit, ihr stellt nur den Raum.',
    ...OFFEN,
    hinweis: 'Jedes Kind bekommt eine Urkunde, auf Wunsch mit kleinem LEGO-Geschenk.',
  },
};

export const isTodo = (value: string | undefined): boolean => !value || value.trimStart().startsWith('TODO');
