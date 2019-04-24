
function updateProctorScores(){
	console.log(this.value);
	console.log(this.dataset.user);
	console.log(this.dataset.assignment);
	console.log(this.dataset.comment);
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
			alert('update success')
		}
	});
}


jQuery( 'select' ).change( updateProctorScores)

let commentBoxes = document.querySelectorAll('input')
commentBoxes.forEach(function(commentBox){
	commentBox.addEventListener('input', function(evt){
    console.log(this.value)
    console.log(this.parentNode.childNodes[0].setAttribute('data-comment', this.value))
  })
})

