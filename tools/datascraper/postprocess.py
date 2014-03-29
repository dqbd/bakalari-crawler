import time, re, json, requests, codecs, sqlite3
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as ec
from random import randint

links = []
unreachable = []
unsearchable = []
database = []

"""driver = webdriver.Firefox()
driver.get("http://google.cz")
assert "Google" in driver.title"""


def load(path):
    global links

    with codecs.open(path, "r", "utf-8") as file:
        data = file.readlines()

    for link in data:
        links.append(link.rstrip())

    if len(links) > 0:
        return True

    return False


def checkurl(url):
    regex = re.compile(
        r'^https?://'  # http:// or https://
        r'(?:(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\.)+[A-Z]{2,6}\.?|'  # domain...
        r'localhost|'  # localhost...
        r'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})' # ...or ip
        r'(?::\d+)?'  # optional port
        r'(?:/?|[/?]\S+)$', re.IGNORECASE)

    if not "://" in url:
        url = "http://" + url

    if regex.search(url):
        return url

    return False


def verify_is_bakalare(url):
    try:
        rq = requests.get(url, verify=False, timeout=20)
        bs = BeautifulSoup(rq.text)
        name = bs.find(class_="nazevskoly")

        if (not name is None) and (not bs.find(id="formbaka") is None):
            return {"status": True, "name": name.text}
        else:
            return {"status": False}

    except requests.exceptions.RequestException as es:
        unreachable.append(url)
        print(str(es))
        return {"status": False}


def require_coords(address):
    rq = requests.get("http://maps.googleapis.com/maps/api/geocode/json", params={
        "address": address,
        "sensor": "true",
        "region": "cz"
    }, verify=False)

    data = json.loads(rq.text)

    if data['status'] == "OK" and len(data["results"]) > 0:
        return data["results"][0]['geometry']['location']

    return None


"""def prepare_database(array, id=None):
    global unsearchable, driver, database

    if id is None:
        id = 0

    url = checkurl(array[id])

    if not url is False:
        integrity = verify_is_bakalare(url)

        if integrity["status"] is True:

            name = integrity["name"]



            database.append({"name":name, "url":url})

            time.sleep(randint(3, 7))

            #try:
            searchbox = driver.find_element_by_xpath('//*[@id="gbqfq"]')
            searchbox.clear()
            searchbox.send_keys(name)
            searchbox.send_keys(Keys.RETURN)

            address = None
            coords = None

            try:
                graph = WebDriverWait(driver, 4).until(ec.presence_of_element_located((By.ID, 'rhs')))

                key = graph.find_element_by_xpath("//span[text()[contains(.,'Adresa')] and @style='font-weight:bold']/..")

                derp = key.find_elements_by_tag_name("span")

                if len(derp) == 2 and "Adresa" in derp[0].text:
                    address = derp[1].text
                    coords = require_coords(derp[1].text)

            except:
                unsearchable.append(url)

            dict = {"name": name, "coords": coords, "address": address, "url": url}

            database.append(dict)
            pretty_print(dict)
            except:

                try:
                    captcha = driver.find_element_by_xpath("//*[@action='CaptchaRedirect' or @value='https://www.google.cz/search?sclient=psy-ab']")

                    wait_till_captcha(array, id)

                    return
                except:
                    raise ValueError


    id += 1

    if id < len(array):
        prepare_database(array, id)
    else:
        pass
        store_to_sqlite()
"""

def prepare_database(array):
    global unsearchable, database

    for url in array:
        url = checkurl(url)

        if url is False:
            continue

        integrity = verify_is_bakalare(url)

        if integrity["status"] is False:
            continue
        else:
            print(integrity['name'])

        database.append({"name": integrity["name"], "url": url})

    store_to_sqlite()

def prepare_plain():

    if load_database_from_plain("parsed.txt") and load_unparsed_from_plain("unparsed.txt"):
        store_to_sqlite()


def load_database_from_plain(src):

    global database
    with codecs.open(src, "r", "utf-8") as file:
        data = file.readline()

        database = json.loads(data)

    return True


def load_unparsed_from_plain(src):
    global unreachable
    with codecs.open(src, "r", "utf-8") as file:
        data = file.readline()

        unreachable = json.loads(data)

    return True


def store_to_sqlite():
    global database, unreachable

    print(json.dumps(unreachable))
    print("-"*30)
    print(json.dumps(database))

    conn = sqlite3.connect("store.db")
    cursor = conn.cursor()

    for dict in database:
        ins = list()

        ins.append(dict["name"])
        ins.append(dict["url"])

        cursor.execute("INSERT INTO parsed (name, url) VALUES (?,?)", ins)

    for unp in unreachable:

        cursor.execute("INSERT INTO unparsed (url) VALUES (?)", [unp])



    conn.commit()
    conn.close()


def wait_till_captcha(array, id):
    while True:
        try:
            driver.find_element_by_xpath("//*[@action='CaptchaRedirect' or @value='https://www.google.cz/search?sclient=psy-ab']")
        except:
            prepare_database(array, id)
            return

def pretty_print(dict):
    print("Název: " + dict["name"])
    print("URL: " + dict["url"])
    if not dict["address"] is None:
        print("Adresa: " + dict["address"])
    if not dict['coords'] is None:
        print("Souřadnice: " + "Lat: " + str(dict["coords"]["lat"]) + ", Lng: " + str(dict["coords"]["lng"]))
    print("-" * 30)


if load("src.txt"):
    prepare_plain()
    #prepare_database(list(set(links)))