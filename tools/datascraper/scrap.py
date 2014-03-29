import requests,sqlite3, json, os

def retrieve_data(name, what):
    request = requests.get("http://ajax.googleapis.com/ajax/services/search/local", params={
        "key": "AIzaSyDPyEP743bagfVQEz9DpCqKjtjTL6HeXn8",
        "v": "1.0",
        "language":"cz",
        "q": what
    }, verify=False)

    print(what)
    store_to_file(name, format_json(request.text))


def store_to_file(name, txt, folder = None):

    if folder is None:
        folder = "gdata/"

    with open(folder + str(name), "wb") as output:
        output.write(bytes(txt, "UTF-8"))

def reverse_geocoding(id):
    with open("gdata/"+str(id), "r", encoding="UTF-8") as input:

        rewrite = json.loads(input.read().replace("\n", ""))
        for i, item in enumerate(rewrite["responseData"]["results"]):
            before = " ".join(item["addressLines"])

            after = retranslate_address(before)
            #rewrite["responseData"]["results"][i]["fixedName"] = retranslate_address(data)
            print(before+" -> "+after)
            #store_to_file(id, )

def retranslate_address(before):
    request = requests.get("http://maps.googleapis.com/maps/api/geocode/json", params={
        "address": str(before),
        "sensor":"true"
    })

    request = json.loads(request.text)

    print(request)
    if len(request["results"]) > 1:
        return request["results"][0]["formatted_address"]
    return ""


def format_json(text):
    return json.dumps(json.loads(text), sort_keys=True, indent=4, separators=(',', ': '))

def reformat(file):
    with open("gdata/"+str(file), "r", encoding="UTF-8") as input:

        store_to_file(file, format_json(input.readline()))

conn = sqlite3.connect("store.db")
cursor = conn.cursor()

for internal in os.listdir("gdata\\"):

    with open("gdata/"+str(internal), "r", encoding="UTF-8") as file:
        cursor.execute("INSERT INTO assign (id, json) VALUES (?,?)", (internal, json.dumps(json.loads(file.read().replace("\n", "")), separators=(',',':'))))

conn.commit()
conn.close()

"""cursor = conn.cursor().execute("SELECT id, name FROM parsed")

for item in cursor.fetchall():
    retrieve_data(item[0], item[1])

cursor.close()"""


