function updateProctorScores(){
	// console.log(this.value);
	// console.log(this.dataset.user);
	// console.log(this.dataset.assignment);
	// console.log(this.dataset.comment);
	const notification = document.getElementById('success-notification');
	var score = this.value;
	var assignment_id = this.dataset.assignment;
	var user_id = this.dataset.user;
	var assignment_comment = this.dataset.comment;
	jQuery.ajax({
		url : proctor_score.ajax_url,
		type : 'post',
		data : {
			action : 'update_proctor_grades',
			user_id : user_id,
			assignment_score : score,
			assignment_id : assignment_id,
			assignment_comment : assignment_comment,
		},
		success : function( response ) {
			notification.innerHTML = 'update success';
		}
	});
}


jQuery( 'select' ).change(updateProctorScores)

jQuery("#updateit").click(updateAll);

if(document.getElementById('updateit')){
	let commentBoxes = document.querySelectorAll('input')
	commentBoxes.forEach(function(commentBox){
		commentBox.addEventListener('input', function(evt){
	    console.log(this.value);
	    this.parentNode.childNodes[0].setAttribute('data-comment', this.value);
	  })
	})
}


function updateAll(){
	let boxes = document.querySelectorAll('select');
	boxes.forEach(function(box){
		console.log(box.id);		
		updateProctorScoresById(box.id);
	})
}

//ugly - need to combine with above to make one function but works for an update all button for now
function updateProctorScoresById(id){
	let box = document.getElementById(id);
	const notification = document.getElementById('success-notification');
	var score = box.value;
	var assignment_id = box.dataset.assignment;
	var user_id = box.dataset.user;
	var assignment_comment = box.dataset.comment;
	jQuery.ajax({
		url : proctor_score.ajax_url,
		type : 'post',
		data : {
			action : 'update_proctor_grades',
			user_id : user_id,
			assignment_score : score,
			assignment_id : assignment_id,
			assignment_comment : assignment_comment,
		},
		success : function( response ) {
			notification.innerHTML = 'update success';
		}
	});
}