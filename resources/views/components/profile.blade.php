
<div class="offcanvas offcanvas-start" tabindex="-1" id="popup-profile" aria-labelledby="popup-profile">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title">Profile</h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="section info-head">
			<span class="material-symbols-outlined filled-icon">person_pin</span>
			<p>{{ $currentUser->employeeId }}</p>
			<p class="name">{{ $currentUser->displayName }}</p>
			<p class="mail">{{ $currentUser->mail }}</p>
		</div>
		<div class="section info-body">
			<p>
				<span>{{ $currentUser->department }}</span>
				<span>{{ $currentUser->title }}</span>
			</p>
			<p>{{ $currentUser->company }}</p>
		</div>
		<a href="{{ route('signout') }}" class="btn btn-signout" type="button">
			<span class="material-symbols-outlined filled-icon">logout</span>
		</a>
	</div>
</div>