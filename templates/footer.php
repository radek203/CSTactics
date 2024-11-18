<script>
	for (let index = 1; index <= 5; ++index) {
		const changeButton = document.getElementById("img-change-" + index);
		if (changeButton != null) {
			changeButton.onclick = () => {
				const id = changeButton.id.split("-")[2];
				for (let changeid = 1; changeid <= 5; ++changeid) {
					const imgToChange = document.getElementById("img-" + changeid);
					if (changeid === parseInt(id)) {
						imgToChange.style.display = "block";
					} else {
						imgToChange.style.display = "none";
					}
				}
			};
		}
	}
</script>