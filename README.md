# Eesti Mälu Instituudi aruanded

See on Eesti Mälu Instituudi andmebaasi statistika repositoorium.

## Nõuded süsteemile

- PHP versioon 8.0 või hilisem

## Installeerimine

```sh
# Clone the repository
git clone git@github.com:Eesti-Malu-Instituut/we-aruanded.git
# Kui ssh ühendust ei soovi tekitada, siis saab ka
git clone https://github.com/Eesti-Malu-Instituut/we-aruanded.git html

# Navigate to the project's directory
cd we-aruanded

# Copy project environment file
cp -n .env.example .env

# Fill project environment file with correct data
vim .env
:x

# copy files to web directory
# (local directory would be /var/www/html)
cp -R -n * /var/www/html

# open your browser at `http://localhost:8000/index.php`

# filters can be added and mysql queries modified in filters.json file

# table rows can be modified in table_rows.json fileAA
```
