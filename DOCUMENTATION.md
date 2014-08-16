FORMAT: 1A
HOST: http://skolar.duong.cz/api/

# Školář
Školář je developer-friendly API pro školní informační systémy. Zároveň je to hlavní zdroj dat pro stejnojmennou aplikaci Školář.

## Technické informace

Formátem pro příjem a odesílání dat je striktně JSON. JSONP zatím podporovaný není, zatím nepočítám s Cross Site Scripting.

SSL/HTTPS je zatím ve fázi "nemám-peníze-čekám-na-financování," ale počítá se s tím a bude následně nezbytnou součástí, vzhledem k povaze celého systému.

Každý požadavek může trvat maximálně 90 sekund, poté se bezprostředně přeruší, kvůli omezení hostingu. 

> Je dost možné, že se tohle v budoucnu změní, třeba pokud přejdeme na jiný hosting atd.

# Group Obecné
Příkazy, které jsem nemohl nikde jinde zařadit...

## Seznam škol [/schoollist/{northeast}/{southwest}/{limit}]
Vypíše všechny školy, které jsou v obdélníku vymezeném dvěma souřadnicemi.

+ Parameters
    + northeast (string) ... Souřadnice **horního pravého** rohu, oddělené čárkou
    + southwest (string) ... Souřadnice **dolního levého** rohu, oddělené čárkou 
    + limit = `60` (numeric, optional) ... Počet škol, maximálně 60

### Získat seznam škol [GET]

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "schoollist": [
                    {
                        "id": 626,
                        "name": "Obchodní akademie, Ostrava-Poruba, příspěvková organizace",
                        "url": "https://85.135.105.187/",
                        "address": "Polská 1543/6, 708 00 Ostrava-Poruba, Česká republika",
                        "latitude": 49.821716,
                        "longitude": 18.192359,
                        "distance": "1.482015470818317"
                    }
                ]
            }
        }


## Testovací soubory [/testfiles/{system}]
Vypíše testovací soubory, které mohou být dosazeny místo přihlašovacích údajů.

+ Parameters
    + system (string) ... Název podporovaného systému, lowercase. Zatím pouze jedna hodnota

        + Values
            + `bakalari`

### Získat seznam souborů [GET]

+ Response 200 (application/json) 
        
        { 
            "status": "ok",
            "data": {
                "testfiles": ["klasifikace-zakladni.html", "rozvrh-volno.html"]            
            }
        }

## Systémová zpráva [/msg]
Někdy je třeba zobrazit zprávu ze serveru, třebaže něco nejede a už se to bude opravovat.

### Získat systémovou zprávu [GET]

+ Response 200 (application/json)
        
        {
            "status": "ok",
            "data": {
                "msg": {
                    "contents": "Došlo k aktualizaci, oprava bude hned",
                    "lastupdated": 1388966400
                }
            }
        }


# Group Bakaláři
Příkazy platné pro [IS Bakaláři](http://bakalari.cz/).

*__POZNÁMKA__: Každý požadavek může trvat až několik sekund (dokonce i minut), proto je dobré nastavit HTTP timeout na limit (90 sekund), cache a případný polling provádět pomocí Batch requestů*

Mezi jednotlivými požadavky sezení (session) zaniká, proto je nutné provést s každým přikázem POST požadavek s přihlašovacími údaji v tomhle formátu:

    Accept: application/json 
    
    {
        "user": "uživ.jméno",
        "pass": "heslo",
        "url": "adresa IS"
    }

Nebo pokud chceme pouze získat výstup ze zkušebního souboru (viz nahoře):

    Accept: application/json 

    {
        "file": "název souboru"
    }

Někdy má daná kategorie možnost dalších zobrazení (třeba suplování na příští týden atd.), proto je možné najít ve výstupu množinu `views` se všemi možnými hodnotami zobrazení. Je dobré počítat s promněnlivostí těchto hodnot, proto se nedoporučuje natvrdo dosazovat hodnoty.

`label` slouží jako název a `value` je to, co odesíláme na server jako parametr.

    {
        "status": "ok",
        "data": {
            
            ...

            "views": [
                {
                    "label": "tento týden",
                    "value": "suplování na tento týden"
                },
                {
                    "label": "příští týden",
                    "value": "suplování na příští týden"
                }
            ]
        }
    }

## Přihlášení [/login]
Přihlášení do IS, popřípadě ověření, zda uživatel existuje. 

### Přihlásit se [POST]
+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)
        
        {
            "status": "ok",
            "data": {
                "login": {
                    "name": "Duong Tat Dat, 5.A",
                    "type": "rodič"
                }
            }
        }


## Navigace [/navigace]
Vypíše všechny přístupné moduly Bakaláří (ne vždy je vše dostupné).

### Přihlásit se [POST]
+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "navigace": [
                    "absence", 
                    "akce", 
                    "predmety", 
                    "rozvrh", 
                    "suplovani", 
                    "ukoly", 
                    "vysvedceni", 
                    "vyuka", 
                    "znamky"
                ]
            }
        }

## Známky [/znamky]
To, o co všichni usilují. Zobrazí známky uživatele.

Známky mohou být buď určeny jako číslo nebo jako množství dosažených bodů, viz. ukázka.

### Zobrazit známky [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" }

+ Response 200 (application/json)
    
        {
            "status": "ok",
            "data": {
                "predmety": [
                    "Matematika",
                    "Fyzika"
                ],
                "znamky": [
                    "0": [
                        {
                            "mark": "1",
                            "caption": "Titulek známky",
                            "date": "18.2.14",
                            "note": "Poznámka učitele",
                            "weight": "1"
                        },
                        {
                            "mark": "5",
                            "caption": "Čtvrtletní práce",
                            "date": "21.3.14",
                            "note": "Proletěl jsi!",
                            "weight": "3"
                        }
                    ],
                    "1": [
                        {
                            "mark": {
                                "gain": "8",
                                "max": "10"
                            },
                            "caption": "Bodový způsob znákování",
                            "date": "13.3.14",
                            "note": "Je to divné, ale existují takové případy",
                            "weight": "1"
                        }
                    ]
                ]
            }
        }

## Suplování [/suplovani/{view}]
Náhlé a neočekávané vysvobození z tyranie jménem *škola*. Změny v rozvrhu uživatele.

+ Parameters
    + view (string, optional) ... Další možné zobrazení, viz. nahoře.

### Zobrazit suplování [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "suplovani": [
                    {
                        "date": 1390176000,
                        "changes": [
                            "3. hod - suplování (Pavel Czernek, Chemie) (Zsv)",
                        ]
                    },
                    {
                        "date": 1390435200,
                        "changes": [
                            "2. hod - změna místnosti (5.A) (M)",
                        ]
                    }
                ],
                "views": [
                    {
                        "label": "tento týden",
                        "value": "suplování na tento týden"
                    },
                    {
                        "label": "příští týden",
                        "value": "suplování na příští týden"
                    }
                ]
            }
        }
    
## Rozvrh [/rozvrh/{view}]
I mučení ma svůj řád. Rozvrh, *časový harmonogram jednotlivých hodin uživatele* 

Jednotlivé pole může obsahovat vícero hodin (půlené hodiny atd).

U volných hodin a u akcí jsou hodiny sdružené do jednoho políčka, které je definované počátkem (`begin`) a délkou (`length`).

+ Parameters
    + view (string, optional) ... Další možné zobrazení, viz. nahoře.

### Zobrazit rozvrh [GET]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "casy": [
                    {
                        "label": "1",
                        "time": ["7:00", "7:45"]
                    },
                    {
                        "label": "2",
                        "time": ["7:50", "8:30"]
                    }
                ],
                "rozvrh": [
                    {
                        "day": {
                            "label": "Po", 
                            "time": 1390176000
                        },
                        "lessons": [
                            {
                                "lesson": 0,
                                "type": "normal",
                                "content": [
                                    {
                                        "name": {
                                            "short": "Ivt",
                                            "long": "Informatika a výp. technika"
                                        },
                                        "teacher": {
                                            "short": "Sta",
                                            "long": "Mgr. Lada Stachovcová"
                                        },
                                        "place": {
                                            "short": "IVT1",
                                            "long": "učebna IVT1 (300)"
                                        },
                                        "group": {
                                            "short": "sk1",
                                            "long": "Skupina 1"
                                        },
                                        "changes": ""
                                    },
                                    {
                                        "name": {
                                            "short": "M",
                                            "long": "Matematika"
                                        },
                                        "teacher": {
                                            "short": "Vav",
                                            "long": "RNDr. Michal Vavroš Ph.D."
                                        },
                                        "place": {
                                            "short": "5.A",
                                            "long": "učebna M1 (309)"
                                        },
                                        "group": {
                                            "short": "sk2",
                                            "long": "Skupina 2"
                                        },
                                        "changes": ""
                                    }
                                ]
                            },
                            {
                                "lesson": 1,
                                "type": "changed",
                                "content": [
                                    {
                                        "name": {
                                            "short": "Zsv",
                                            "long": "Základy společenských věd"
                                        },
                                        "teacher": {
                                            "short": "Nez",
                                            "long": "PhDr. Petr Nezdařil"
                                        },
                                        "place": {
                                            "short": "5.A",
                                            "long": "učebna M1 (309)"
                                        },
                                        "group": {
                                            "short": "",
                                            "long": ""
                                        },
                                        "changes": "požární poplach"
                                    }
                                ]
                            }
                        ]
                    }, 
                    {
                        "day": {
                            "label": "Út",
                            "time": 1390262400
                        },
                        "lessons": [
                            {
                                "lesson": {
                                    "begin": 0,
                                    "length": 2
                                },
                                "type": "free",
                                "content": [
                                    {
                                        "name": {
                                            "short": "Prázdniny",
                                            "long": "Prázdniny"
                                        },
                                        "teacher": {
                                            "short": "",
                                            "long": ""
                                        },
                                        "place": {
                                            "short": "",
                                            "long": ""
                                        },
                                        "group": {
                                            "short": "",
                                            "long": ""
                                        },
                                        "changes": ""
                                    }
                                ]
                            }
                        ]
                    }
                ],
                "views": [
                    {
                        "label": "rozvrh na tento týden",
                        "value": "rozvrh na tento týden"
                    },
                    {
                        "label": "rozvrh na příští týden",
                        "value": "rozvrh na příští týden"
                    },
                    {
                        "label": "stálý rozvrh",
                        "value": "stálý rozvrh"
                    }
                ]
            }
        }

## Akce [/akce/{view}]
Události a aktivity uživatele

+ Parameters
    + view (string, optional) ... Další možné zobrazení, viz. nahoře.

### Vypsat akce [GET]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "akce": [
                    {
                        "name": "Super školní akce",
                        "datetime": {
                            "date": 1388966400,
                            "time": ["16:30", "18:30"]
                        },
                        "teacher": ["Bar", "Hla", "Nov", "PiL", "Vav"],
                        "class": ["1.C", "5.A"],
                        "place": "U-Bi",
                        "detail": "Nepovinné, ale povinné"
                    }
                ],
                "views": [
                    {
                        "label": "týden 30.12. - 3.1.",
                        "value": "týden 30.12. - 3.1."
                    },
                    {
                        "label": "týden 6.1. - 10.1.",
                        "value": "týden 6.1. - 10.1."
                    },
                    {
                        "label": "měsíc leden",
                        "value": "měsíc leden"
                    },
                    {
                        "label": "měsíc únor",
                        "value": "měsíc únor"
                    },
                    {
                        "label": "pololetí",
                        "value": "pololetí"
                    }
                ]
            }
        }

## Absence [/absence]
Který student by chtěl být ve škole? Množství absence uživatele.

### Získat tabulku absence [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "absence": [
                    {
                        "name": "Český jazyk a literatura",
                        "total": "60",
                        "missing": "1"
                    }
                ]
            }
        }

## Výuka [/vyuka/{subject}/{page}]
Podrobná tabulka výuky vybraného uživatele.

+ Parameters
    + subject (string, optional) ... Předmět, který se má zobrazit
    + page = `1` (number, optional) ... Stránka, která se má zobrazit

### Získat seznam vyučování [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "vyuka": [
                    {
                        "date": 1446336000,
                        "lesson": "2",
                        "topic": "Mučení II",
                        "detail": "Studenti nic moc",
                        "number": "69"
                    }
                ],
                "pages": [
                    "1",
                    "2"
                ],
                "views": [
                    {
                        "label": "Český jazyk",
                        "value": "Čj"
                    },
                    {
                        "label": "Anglický jazyk",
                        "value": "Aj"
                    }
                ]
            }
        }

## Úkoly [/ukoly]
Cvičení, které doděláváme o přestávce. Vypíše všechny domácí úkoly, které uživatel dostal. 

Počítám s tím, že bude posléze možné přidávat ručně úkoly (vlastní databáze)

### Získat domácí úkoly [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json) 

        {
            "status": "ok",
            "data": {
                "ukoly": [
                    {
                        "date": 1394060400,
                        "subject": "Matematika",
                        "detail": "s evidencí odevzdání, Borská neodevzdá"
                    }
                ]
            }
        }

## Vysvědčení [/vysvedceni]
Takový papír, za něhož dostaneš buď slevu do Ollies nebo zaracha. Zobrazí prospěch všech ročníků uživatele.

### Získat prospěch žáka [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "vysvedceni": {
                    "1": {
                        "1": ["1","2","1"],
                        "2": ["1","-","4"]
                    }
                },
                "rocniky": [
                    "první"
                ],
                "predmety": [
                    "Chování",
                    "Český jazyk a literatura",
                    "Anglický jazyk"
                ]
            }
        }

## Předměty [/predmety]
Všechny předměty, které musí uživatel protrpět každoročně 5 dní v týdnu po dobu 9-10 měsíců. Zobrazí seznam všech předmětů a některé zajimavé informace o kantorech, jako je jejich telefonní číslo ;)

Jelikož nejsme si moc jistí, jak bude tabulka vypadat, nemám moc dat, proto je to rozložené na `hlavicka` a na `predmety`. 

### Získat seznam předmětů [POST]

+ Request (application/json)

        { "user": "uživ.jméno", "pass": "heslo", "url": "adresa IS" } 

+ Response 200 (application/json) 

        {
            "status": "ok",
            "data": {
                "hlavicka": [
                    "Předmět",
                    "Učitel"
                ],
                "predmety": [
                    [
                        "Český jazyk a literatura",
                        "PhDr. Lukáš Průša Ph.D."
                    ]
                ]
            }
        }

## Batch request [/batch]
Některé požadavky mohou trvat až desítky sekund, které se mohou hromadit když jsou prováděny seriárně. Pokud je třeba několik požadavků smrsknout do jednoho, můžeme použít batch request.

Ne vždy je však batch request ta správná volba, jelikož ten požadavek je časově omezen (viz nahoře). Nejlepší volbou je sledovat čas požadavku a následně se rozhodnout, zda to vše hodit do jednoho batch požadavku, nebo spustit několik požadavků pararelně. Pokud ale nemáme čaš, upřímně stačí batch požadavek. 

Doba požadavku je rovna: dobu požadavku loginu + nejdelší doba stahování z požadovaného požadavku. 

Požadované požadavky (ha) se definují POST parametrem `requests`, oddělené čárkou. 

Jednotlivé parametry se odesílají pomocí POST požadavku s prefixem `requestparam-{stránka}-{název parametru}`, viz příklad.

`file` parametry nejsou podporované a budou ignorovány, protože jsem líný a nevidím důvod. Možná někdy jindy?

### Provést batch požadavek [POST]

+ Request (application/json)

        {
            "user": "uživ.jméno",
            "pass": "heslo",
            "url": "adresa IS",
            "requests": "znamky,suplovani",
            "requestparam-suplovani-view": "příští týden"
        }

+ Response 200 (application/json)

        {
            "status": "ok",
            "data": {
                "znamky": {
                    "predmety": [
                        "Matematika",
                        "Fyzika"
                    ],
                    "znamky": [
                        "0": [
                            {
                                "mark": "1",
                                "caption": "Titulek známky",
                                "date": "18.2.14",
                                "note": "Poznámka učitele",
                                "weight": "1"
                            }
                        ]
                    ]
                },
                "suplovani": {
                    "suplovani": [
                        {
                            "date": 1390176000,
                            "changes": [
                                "3. hod - suplování (Pavel Czernek, Chemie) (Zsv)",
                            ]
                        },
                        {
                            "date": 1390435200,
                            "changes": [
                                "2. hod - změna místnosti (5.A) (M)",
                            ]
                        }
                    ],
                    "views": [
                        {
                            "label": "tento týden",
                            "value": "suplování na tento týden"
                        },
                        {
                            "label": "příští týden",
                            "value": "suplování na příští týden"
                        }
                    ]
                }
            }
        }