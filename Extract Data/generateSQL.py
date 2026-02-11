import csv

# Read the structured CSV file
file_path = 'extracted_school_data.csv'
table_name = 'schools'

# Open the structured CSV file and read its contents
with open(file_path, 'r') as csvfile:
    csv_reader = csv.DictReader(csvfile)
    with open('insert.sql', 'w') as sqlfile:  # Overwrites the file
        for row in csv_reader:
            columns = ', '.join(row.keys())
            values = ', '.join([f"'{value}'" if value else 'NULL' for value in row.values()])
            sql_query = f"INSERT INTO {table_name} ({columns}) VALUES ({values}) ON DUPLICATE KEY UPDATE "
            updates = ', '.join([f"{key} = VALUES({key})" for key in row.keys() if key != 'id'])
            sql_query += updates + ';'

            # Writes the SQL query to the file
            sqlfile.write(sql_query + '\n')

        print(f"SQL statements have been written to insert.sql")