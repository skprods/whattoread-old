timestamp() {
  date +"%Y-%m-%d_%H-%M-%S" # current time
}

mysqldump --user "$1" --password="$2" "$3" > /home/"$3"_"$(timestamp)".dump
