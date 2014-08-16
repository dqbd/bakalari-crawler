# Poznatky
 - Když neexistuje cache, načti login.aspx jako úvod a pak proveď login
 - Když už jsi přihlášen, tak na to kašleš
 	- Jako přihlášený můžeš navštevovat stránky! 
 - Navigaci musíme vygenerovat pokaždé, mění se s session. 

# TODO
## Hotovo
- Upravit a přejmenovat `map` -> `schoollist`
	- Upravit její parametry
- Přidat testfiles metodu
- Přidat batch request
- Upravit modul navigace
- Upravit login tak, aby byl uzavren v jeden array
- Opravit FormFieldRegistry tak, aby nebyl třeba fork DomCrawleru
- Upravit parametry funkci
	- Suplovani -> view
	- Rozvrh -> view
	- Akce -> view
	- Vyuka -> subject, page
- Nastavit timeout na 90 sekund
- Opravit datum u rozvrhu - timestamp!

## Není hotovo
- Publikovat opravené změny na production
- Udělej kód testovatelný
	- Zbav se statických metod a užívej vždy instantizaci
		- Configuration
		- Toolkits (a BakalariToolkit)
		- Dispatcher a jeho CreateModule, CreateHandler metody
		- Utils
	- Přidej nějaké továrny pro jednoduchou instantizaci několika objektů naráz
	- Rozděl BakalariHandler na několik metod